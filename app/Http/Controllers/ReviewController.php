<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Helpers\JsonLd;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\ScreenshotReview;
use App\Models\ScreenshotReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $authors = User::has('reviews')
            ->get();

        $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED);

        if ($request->filled('author')) {
            $reviews->whereHas('user', function (Builder $query) use ($request) {
                $query->where('user_id', $request->input('author'));
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
                ->where('review_id', '!=', $review->review_id)
                ->get();
        }

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', 'Review of '.$review->games->first()->game_name)
            ->add('author', Helper::user($review->user))
            ->add('datePublished', date('Y-m-d', $review->review_date));
        if ($review->screenshots->isNotEmpty() && $review->screenshots->first()->screenshot !== null) {
            $jsonLd->add('image', $review->screenshots->first()->screenshot->getUrl('game'));
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
            return response(400);
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

        $request->user()->reviews()->save($review);
        $game->reviews()->save($review);

        $score = new ReviewScore();
        $score->review_graphics = $request->graphics ?? 0;
        $score->review_sound = $request->sound ?? 0;
        $score->review_gameplay = $request->gameplay ?? 0;
        $score->review_overall = $request->overall ?? 0;

        $review->score()->save($score);

        // Process screenshots comments. Screenshots were ordered by screenshot_id
        // so we should iterate over the same ordered list of game screenshots to
        // associate the comment with the correct screenshot
        $gameScreenshots = $game->screenshots->sortBy('screenshot_id');
        if ($request->filled('screenshot')) {
            $i = 0;
            foreach ($request->screenshot as $screenshotComment) {
                $gameScreenshot = $gameScreenshots[$i++];

                $screenshotReview = new ScreenshotReview();
                $screenshotReview->review_id = $review->review_id;
                $screenshotReview->screenshot_id = $gameScreenshot->screenshot_id;

                $screenshotReview->save();

                $comment = new ScreenshotReviewComment();
                $comment->comment_text = $screenshotComment;
                $screenshotReview->comment()->save($comment);
            }
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Reviews',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Submission',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
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
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $review->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $review->games->first()->game_name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $review->games->first()->game_name,
        ]);

        return back();
    }

    private function getReviewsForUser(User $user)
    {
        return Review::where('user_id', $user->user_id)
            ->where('review_edit', Review::REVIEW_PUBLISHED);
    }
}
