<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Review;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $review->games[0]->game_name,
            'sub_section'      => 'Review',
            'sub_section_id'   => $review->getKey(),
            'sub_section_name' => $review->games[0]->game_name,
        ]);

        return redirect()->route('admin.reviews.' . ($request->submission ? 'submissions' : 'reviews') . '.index');
    }

    private function getValidationRules(): array
    {
        return [
            'author'     => 'required|exists:users,user_id',
            'date'       => 'required|date',
            'text'       => 'required',
            'draft'      => 'nullable',
            'submission' => 'nullable',
        ];
    }
}
