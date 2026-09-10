<?php

namespace App\Http\Controllers\Admin\User;

use App\Models\InterviewComment;

class InterviewCommentController extends CommentController
{
    protected string $section = 'interviews';

    protected string $heading = 'Interview comments';

    protected function model(): string
    {
        return InterviewComment::class;
    }

    protected function table(): string
    {
        return 'admin.interview-comments-table';
    }
}
