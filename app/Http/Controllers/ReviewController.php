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
        return view('reviews.show')
            ->with(["review" => $review]);
    }
}
