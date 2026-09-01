<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Helpers\JsonLd;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Review;
use App\Models\ScreenshotReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $authors = User::has('reviews')
            ->get();

        $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED);

        if ($request->filled('author')) {
            $reviews->whereHas('user', function (Builder $query) use ($request) {
                // Inside whereHas the query runs against `users`, so this is
                // User's own key rather than reviews's foreign key.
                $query->where('users.id', $request->input('author'));
            });
        }

        $reviews = $reviews
            ->orderByDesc('review_date')
            ->paginate(5);

        return view('reviews.index')
            ->with([
                'reviews' => $reviews,
                'authors' => $authors,
            ]);
    }

    public function show(Review $review)
    {
        $otherReviews = collect([]);

        if (isset($review->user)) {
            $otherReviews = $this->getReviewsForUser($review->user)
                ->whereKeyNot($review->getKey())
                ->get();
        }

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', 'Review of ' . $review->games->first()->name)
            ->add('author', Helper::user($review->user))
            ->add('datePublished', $review->review_date->format('Y-m-d'));
        if ($review->screenshots->isNotEmpty()) {
            $jsonLd->add('image', $review->screenshots->first()->getUrlRoute('game', $review->games->first()));
        }

        return view('reviews.show')
            ->with([
                'review'       => $review,
                'otherReviews' => $otherReviews,
                'jsonLd'       => $jsonLd,
            ]);
    }

    public function edit(Request $request)
    {
        if (! $request->filled('game')) {
            // response(400) would have set the *body* to '400' and left the
            // status at 200, so a form with no game looked like a success.
            abort(400);
        }

        $game = Game::find($request->game);

        $otherReviews = $this->getReviewsForUser(Auth::user())
            ->get();

        return view('reviews.submit')
            ->with([
                'game'          => $game,
                'otherReviews'  => $otherReviews,
            ]);
    }

    public function submit(Request $request)
    {
        $game = Game::find($request->game);

        $review = new Review();
        $review->review_text = $request->text;
        $review->review_date = time();
        $review->review_edit = Review::REVIEW_UNPUBLISHED;
        // Set before the first save, not after it: the scores are columns on
        // the review now, so filling them here is one insert where the old
        // score row needed a second write. A submitted review with no scores
        // still reads as zeros rather than as "unscored".
        $review->review_graphics = $request->graphics ?? 0;
        $review->review_sound = $request->sound ?? 0;
        $review->review_gameplay = $request->gameplay ?? 0;
        $review->review_overall = $request->overall ?? 0;

        $request->user()->reviews()->save($review);
        $game->reviews()->save($review);

        // Process screenshots comments. Screenshots were ordered by screenshot_id
        // so we should iterate over the same ordered list of game screenshots to
        // associate the comment with the correct screenshot
        $gameScreenshots = $game->screenshots->sortBy('id');
        if ($request->filled('screenshot')) {
            $i = 0;
            foreach ($request->screenshot as $screenshotComment) {
                $gameScreenshot = $gameScreenshots[$i++];

                if ($screenshotComment !== null) {
                    $id = DB::table('screenshot_review')
                        ->insertGetId([
                            'review_id'     => $review->getKey(),
                            'screenshot_id' => $gameScreenshot->getKey(),
                        ]);
                    $comment = new ScreenshotReviewComment();
                    $comment->text = $screenshotComment;
                    $comment->screenshot_review_id = $id;
                    $comment->save();
                }
            }
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Reviews',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->name,
            'sub_section'      => 'Submission',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->name,
        ]);

        $request->session()->flash('alert-title', 'Review submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return redirect()->route('games.show', [$game]);
    }

    public function postComment(Review $review, Request $request)
    {
        $comment = new Comment();
        $comment->text = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $review->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $review->games->first()->name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $review->games->first()->name,
        ]);

        return back();
    }

    private function getReviewsForUser(User $user)
    {
        return Review::where('user_id', $user->getKey())
            ->where('review_edit', Review::REVIEW_PUBLISHED)
            ->orderBy('review_date', 'desc');
    }
}
