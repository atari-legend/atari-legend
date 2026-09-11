<?php

namespace App\Livewire\Admin;

use App\Models\ReviewComment;

class ReviewCommentsTable extends CommentsTable
{
    protected function model(): string
    {
        return ReviewComment::class;
    }

    protected function userRelation(): string
    {
        return 'reviewComments';
    }

    protected function section(): string
    {
        return 'reviews';
    }

    protected function targetHeading(): string
    {
        return 'Game';
    }
}
