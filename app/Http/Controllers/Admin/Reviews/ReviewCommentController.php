<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Admin\CommentController;
use App\Models\ReviewComment;

class ReviewCommentController extends CommentController
{
    protected string $section = 'reviews';

    protected string $heading = 'Review comments';

    protected function model(): string
    {
        return ReviewComment::class;
    }

    protected function table(): string
    {
        return 'admin.review-comments-table';
    }
}
