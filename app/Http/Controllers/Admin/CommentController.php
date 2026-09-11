<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Comment;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

/**
 * One comment screen per section. What a comment is on is the class, not
 * something worked out from the row, so the section and the route prefix are
 * declared by the subclass and everything else is shared.
 */
abstract class CommentController extends Controller
{
    /**
     * The section this screen belongs to: `games`, `articles`, `interviews`
     * or `reviews`. Names the admin route group, and the public `show` route
     * the edit form links back through.
     */
    protected string $section;

    /**
     * What the breadcrumb and the nav entry call this screen.
     */
    protected string $heading;

    /**
     * @return class-string<Comment> The model this screen lists
     */
    abstract protected function model(): string;

    /**
     * The Livewire table component, as the tag `<livewire:...>` takes.
     */
    abstract protected function table(): string;

    public function index()
    {
        return view('admin.comments.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route($this->routeName('index')), $this->heading),
                ],
                'heading' => $this->heading,
                'table'   => $this->table(),
            ]);
    }

    public function edit(int $comment)
    {
        $comment = $this->model()::findOrFail($comment);

        $label = $comment->created_at?->toDayDateTimeString()
            . ' by ' . Helper::user($comment->user);

        return view('admin.comments.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route($this->routeName('index')), $this->heading),
                    new Crumb(route($this->routeName('edit'), $comment), $label),
                ],
                'comment' => $comment,
                'section' => $this->section,
            ]);
    }

    public function update(Request $request, int $comment)
    {
        $comment = $this->model()::findOrFail($comment);

        $comment->text = $request->content;
        $comment->save();

        $this->changelog(Changelog::UPDATE, $comment);

        return redirect()->route($this->routeName('index'));
    }

    public function destroy(int $comment)
    {
        $comment = $this->model()::findOrFail($comment);

        // The target is reachable through a foreign key that is still there
        // while the row is, so it can be read after the delete.
        $this->changelog(Changelog::DELETE, $comment);

        $comment->delete();

        return redirect()->route($this->routeName('index'));
    }

    private function changelog(string $action, Comment $comment): void
    {
        ChangelogHelper::insert([
            'action'           => $action,
            'section'          => $comment::SECTION,
            'section_id'       => $comment->target_id,
            'section_name'     => $comment->target,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $comment->target,
        ]);
    }

    private function routeName(string $action): string
    {
        return "admin.{$this->section}.comments.{$action}";
    }
}
