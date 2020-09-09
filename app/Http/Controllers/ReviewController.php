<?php

namespace App\Http\Controllers;

use App\GameComment;
use App\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $authors = \App\User::has('reviews')
            ->get();

        $reviews = \App\Review::select();

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

    public function show(\App\Review $review)
    {
        $otherReviews = collect([]);

        if (isset($review->user)) {
            $otherReviews = Review::where('user_id', $review->user->user_id)
                ->where('review_id', '!=', $review->review_id)
                ->get();
        }

        return view('reviews.show')
            ->with([
                'review'       => $review,
                'otherReviews' => $otherReviews,
            ]);
    }

    public function postComment(Review $review, Request $request)
    {
        $comment = new GameComment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $review->comments()->save($comment);

        return back();
    }
}
