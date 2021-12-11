<?php

namespace App\Http\Controllers\Admin\User;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Comment;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    const COMMENT_CHANGELOG_SECTIONS = [
        Comment::TYPE_GAME => 'Games',
        Comment::TYPE_ARTICLE => 'Articles',
        Comment::TYPE_INTERVIEW => 'Interviews',
        Comment::TYPE_REVIEW => 'Reviews',
    ];

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

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => self::COMMENT_CHANGELOG_SECTIONS[$comment->type],
            'section_id'       => $comment->target_id,
            'section_name'     => $comment->target,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $comment->target,
        ]);

        return redirect()->route('admin.users.comments.index');
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => self::COMMENT_CHANGELOG_SECTIONS[$comment->type],
            'section_id'       => $comment->target_id,
            'section_name'     => $comment->target,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $comment->target,
        ]);

        return redirect()->route('admin.users.comments.index');
    }
}
