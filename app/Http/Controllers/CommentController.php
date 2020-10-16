<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function delete(Request $request)
    {
        if ($request->filled('comment_id')) {
            $comment = Comment::find($request->comment_id);

            if (Auth::user()->user_id === $comment->user->user_id) {
                $comment->delete();
            }
        }

        return back();
    }

    public function update(Request $request)
    {
        if ($request->filled('comment_id') && $request->filled('comment')) {
            $comment = Comment::find($request->comment_id);

            if (Auth::user()->user_id === $comment->user->user_id) {
                $comment->comment = $request->comment;
                $comment->timestamp = time();
                $comment->save();
            }
        }

        return back();
    }
}
