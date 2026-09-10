<?php

namespace App\Livewire\Admin;

use App\Models\InterviewComment;

class InterviewCommentsTable extends CommentsTable
{
    protected function model(): string
    {
        return InterviewComment::class;
    }

    protected function userRelation(): string
    {
        return 'interviewComments';
    }

    protected function section(): string
    {
        return 'interviews';
    }

    protected function targetHeading(): string
    {
        return 'Individual';
    }
}
