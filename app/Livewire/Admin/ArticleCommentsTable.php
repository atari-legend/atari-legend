<?php

namespace App\Livewire\Admin;

use App\Models\ArticleComment;

class ArticleCommentsTable extends CommentsTable
{
    protected function model(): string
    {
        return ArticleComment::class;
    }

    protected function userRelation(): string
    {
        return 'articleComments';
    }

    protected function section(): string
    {
        return 'articles';
    }

    protected function targetHeading(): string
    {
        return 'Article';
    }
}
