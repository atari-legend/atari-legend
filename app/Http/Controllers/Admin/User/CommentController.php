<?php

namespace App\Http\Controllers\Admin\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {
        return view('admin.users.comments.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.users.comments.index'), 'Comments'),
                ],
            ]);
    }

    public function edit(Comment $comment)
    {
        $label = Carbon::createFromTimestamp($comment->timestamp)->toDayDateTimeString()
            .' by '.Helper::user($comment->user);

        return view('admin.users.comments.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.users.comments.index'), 'Comments'),
                    new Crumb(route('admin.users.comments.edit', $comment), $label),
                ],
                'comment'        => $comment,
            ]);
    }

    public function update(Request $request, Comment $comment)
    {
        $comment->comment = $request->content;
        $comment->save();

        return redirect()->route('admin.users.comments.index');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return redirect()->route('admin.users.comments.index');
    }
}
