<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Http\Controllers\Admin\CommentController;
use App\Models\ArticleComment;

class ArticleCommentController extends CommentController
{
    protected string $section = 'articles';

    protected string $heading = 'Article comments';

    protected function model(): string
    {
        return ArticleComment::class;
    }

    protected function table(): string
    {
        return 'admin.article-comments-table';
    }
}
