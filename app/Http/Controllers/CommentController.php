<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\Interview;
use App\Models\InterviewComment;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * A comment id is only unique within its own table now, so the posted
     * context is what says which table to look in.
     */
    const MODELS = [
        'game'      => GameComment::class,
        'article'   => ArticleComment::class,
        'interview' => InterviewComment::class,
        'review'    => ReviewComment::class,
    ];

    public function delete(Request $request)
    {
        if ($request->filled('comment_id')) {
            $comment = $this->find($request->context, $request->comment_id);

            if ($comment !== null && Auth::user()->getKey() === $comment->user->getKey()) {
                $comment->delete();

                $this->insertChangelog(Changelog::DELETE, $request->context, $request->id, $comment);
            }
        }

        return back();
    }

    public function update(Request $request)
    {
        if ($request->filled('comment_id') && $request->filled('comment')) {
            $comment = $this->find($request->context, $request->comment_id);

            if ($comment !== null && Auth::user()->getKey() === $comment->user->getKey()) {
                $comment->text = $request->comment;
                $comment->save();

                $this->insertChangelog(Changelog::UPDATE, $request->context, $request->id, $comment);
            }
        }

        return back();
    }

    private function find(?string $context, int $id): ?Comment
    {
        $model = self::MODELS[$context] ?? null;

        return $model === null ? null : $model::find($id);
    }

    public function insertChangelog(string $action, ?string $context, ?int $id, object $comment)
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
                $sectionName = Game::find($id)->name;
                break;
            case 'review':
                $section = 'Reviews';
                $sectionName = Review::find($id)->game->name;
                break;
            case 'interview':
                $section = 'Interviews';
                $sectionName = Interview::find($id)->individual->name;
                break;
            case 'article':
                $section = 'Articles';
                $sectionName = Article::find($id)->title;
                break;
        }

        ChangelogHelper::insert([
            'action'           => $action,
            'section'          => $section,
            'section_id'       => $id,
            'section_name'     => $sectionName,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $sectionName,
        ]);
    }
}
