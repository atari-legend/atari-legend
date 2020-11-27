<?php

namespace App\Helpers\BBCode;

class GameBBCodeTag extends SectionBBCodeTag
{
    public function __construct()
    {
        parent::__construct('game', 'games.show');
    }
}
