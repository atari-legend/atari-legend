<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\ScreenshotReviewComment;
use App\Models\User;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReviewsController extends Controller
{
    public function index()
    {
        return view('admin.reviews.reviews.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.reviews.reviews.index'), 'Reviews'),
                ],
            ]);
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.reviews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.reviews.reviews.index'), 'Reviews'),
                    new Crumb('', $review->games[0]->game_name),
                ],
                'review' => $review,
            ]);
    }

    public function create()
    {
        return view('admin.reviews.reviews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.reviews.reviews.index'), 'Reviews'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(array_merge(
            $this->getValidationRules(),
            ['game' => 'required|exists:game,game_id']));

        $review = new Review([
            'user_id'     => $request->author,
            'draft'       => $request->draft ? true : false,
            'review_edit' => $request->submission ? Review::REVIEW_UNPUBLISHED : Review::REVIEW_PUBLISHED,
            'review_text' => $request->text,
            'review_date' => Carbon::parse($request->date)->timestamp,
        ]);

        $game = Game::findOrFail($request->game);
        $game->reviews()->save($review);

        $user = User::findOrFail($request->author);
        $user->reviews()->save($review);

        $score = new ReviewScore();
        $score->review_graphics = $request->graphics ?? 0;
        $score->review_sound = $request->sound ?? 0;
        $score->review_gameplay = $request->gameplay ?? 0;
        $score->review_overall = $request->overall ?? 0;
        $review->score()->save($score);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $review->games[0]->game_name,
            'sub_section'      => 'Review',
            'sub_section_id'   => $review->getKey(),
            'sub_section_name' => $review->games[0]->game_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.reviews.reviews.edit', $review);
        } else {
            return redirect()->route('admin.reviews.reviews.index');
        }
    }

    public function update(Request $request, Review $review)
    {
        $request->validate($this->getValidationRules());

        $review->update([
            'user_id'     => $request->author,
            'draft'       => $request->draft ? true : false,
            'review_edit' => $request->submission ? Review::REVIEW_UNPUBLISHED : Review::REVIEW_PUBLISHED,
            'review_text' => $request->text,
            'review_date' => Carbon::parse($request->date)->timestamp,
        ]);

        $score = $review->score ?? new ReviewScore();
        $score->review_graphics = $request->graphics ?? 0;
        $score->review_sound = $request->sound ?? 0;
        $score->review_gameplay = $request->gameplay ?? 0;
        $score->review_overall = $request->overall ?? 0;
        $review->score()->save($score);

        collect($request->all())
            ->filter(fn ($v, $k) => Str::startsWith($k, 'screenshot_comment_'))
            ->each(function ($value, $key) use ($review) {
                $screenshotId = (int) str_replace('screenshot_comment_', '', $key);
                $screenshot = $review->getScreenshotComment($screenshotId);
                if ($screenshot?->pivot?->comment && $value !== null) {
                    $screenshot->pivot->comment->comment_text = $value;
                    $screenshot->pivot->comment->save();
                } elseif ($screenshot?->pivot && $value === null) {
                    // Screenshot comment exists but now should be removed
                    $screenshot->pivot->delete();
                } elseif ($value !== null) {
                    // Screenshot comment does not exist, create new pivot and comment
                    $id = DB::table('screenshot_review')
                        ->insertGetId([
                            'review_id'     => $review->review_id,
                            'screenshot_id' => $screenshotId,
                        ]);
                    $comment = new ScreenshotReviewComment();
                    $comment->comment_text = $value;
                    $comment->screenshot_review_id = $id;
                    $comment->save();
                }
            });

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $review->games[0]->game_name,
            'sub_section'      => 'Review',
            'sub_section_id'   => $review->getKey(),
            'sub_section_name' => $review->games[0]->game_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.reviews.reviews.edit', $review);
        } else {
            return redirect()->route('admin.reviews.' . ($request->submission ? 'submissions' : 'reviews') . '.index');
        }
    }

    public function destroy(Review $review)
    {
        $reviewGameName = $review->games[0]->game_name;

        $review->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $reviewGameName,
            'sub_section'      => 'Review',
            'sub_section_id'   => $review->getKey(),
            'sub_section_name' => $reviewGameName,
        ]);

        return redirect()->route('admin.reviews.reviews.index');
    }

    private function getValidationRules(): array
    {
        return [
            'author'     => 'required|exists:users,user_id',
            'date'       => 'required|date',
            'text'       => 'required',
            'draft'      => 'nullable',
            'submission' => 'nullable',
            'graphics'   => 'required|integer|min:0|max:10',
            'sound'      => 'required|integer|min:0|max:10',
            'gameplay'   => 'required|integer|min:0|max:10',
            'overall'    => 'required|integer|min:0|max:10',
        ];
    }
}
