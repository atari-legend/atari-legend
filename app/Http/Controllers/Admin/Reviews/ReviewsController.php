<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Review;
use App\View\Components\Admin\Crumb;

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
                    new Crumb(route('admin.reviews.reviews.edit', $review), $review->games->first()->game_name),
                ],
                'review'      => $review,
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

    public function destroy(Review $review)
    {
        $gameName = $review->games->first()->game_name;

        foreach ($review->comments as $comment) {
            $comment->delete();
        }

        $review->score?->delete();
        $review->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Reviews',
            'section_id'       => $review->getKey(),
            'section_name'     => $gameName,
            'sub_section'      => 'Review',
            'sub_section_id'   => $review->getKey(),
            'sub_section_name' => $gameName,
        ]);

        return redirect()->route('admin.reviews.reviews.index');
    }
}
