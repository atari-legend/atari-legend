<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ReviewController extends Controller
{

    public function index(Request $request)
    {
        $authors = \App\User::has("reviews")
            ->get();

        $reviews = \App\Review::select();

        if ($request->filled('author')) {
            $reviews->whereHas("user", function (Builder $query) use ($request) {
                $query->where("user_id", $request->input("author"));
            });
        }

        $reviews = $reviews
            ->orderByDesc("review_date")
            ->paginate(5);

        return view('reviews.index')
            ->with([
                "reviews" => $reviews,
                "authors" => $authors,
            ]);
    }

    public function show(\App\Review $review)
    {
        $otherReviews = collect([]);

        if (isset($review->user)) {
            $otherReviews = $review->user->reviews
                ->reject(function ($value, $key) use ($review) {
                    return $value->review_id === $review->review_id;
                })
                ->sort(function ($a, $b) {
                    return strcmp(
                        $a->games->first->get()->game_name,
                        $b->games->first->get()->game_name);
                });
        }

        return view('reviews.show')
            ->with([
                "review" => $review,
                "otherReviews" => $otherReviews
            ]);
    }
}
