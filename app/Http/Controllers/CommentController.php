<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Article;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Review;
use App\Models\Interview;
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

                $this->insertChangelog(Auth::user(), Changelog::DELETE, $request->context, $request->id, $comment);
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

                $this->insertChangelog(Auth::user(), Changelog::UPDATE, $request->context, $request->id, $comment);
            }
        }

        return back();
    }

    public function insertChangelog(object $user, string $action, ?string $context, ?int $id, object $comment)
    {
        if ($context === null || $id === null) {
            // Missing context to fill the changelog. May happen if the comment
            // is edited on a page that is not a specific game, interview,
            // article or review
            return;
        }

        $section = null;
        $sectionName = null;
        switch ($context) {
            case 'game':
                $section = 'Games';
                $sectionName = Game::find($id)->game_name;
                break;
            case 'review':
                $section = 'Reviews';
                $sectionName = Review::find($id)->games->first()->game_name;
                break;
            case 'interview':
                $section = 'Interviews';
                $sectionName = Interview::find($id)->individual->ind_name;
                break;
            case 'article':
                $section = 'Articles';
                $sectionName = Article::find($id)->texts->first()->article_title;
                break;
        }

        ChangelogHelper::insert([
            'action'           => $action,
            'section'          => $section,
            'section_id'       => $id,
            'section_name'     => $sectionName,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->comments_id,
            'sub_section_name' => $sectionName,
        ]);
    }
}
