<?php

namespace App\Livewire\Admin;

use App\Models\GameComment;

class GameCommentsTable extends CommentsTable
{
    protected function model(): string
    {
        return GameComment::class;
    }

    protected function userRelation(): string
    {
        return 'gameComments';
    }

    protected function section(): string
    {
        return 'games';
    }

    protected function targetHeading(): string
    {
        return 'Game';
    }
}
