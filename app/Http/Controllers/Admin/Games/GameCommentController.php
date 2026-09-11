<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Admin\CommentController;
use App\Models\GameComment;

class GameCommentController extends CommentController
{
    protected string $section = 'games';

    protected string $heading = 'Game comments';

    protected function model(): string
    {
        return GameComment::class;
    }

    protected function table(): string
    {
        return 'admin.game-comments-table';
    }
}
