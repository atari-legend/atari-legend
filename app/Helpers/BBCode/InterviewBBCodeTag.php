<?php

namespace App\Helpers\BBCode;

class InterviewBBCodeTag extends SectionBBCodeTag
{
    public function __construct()
    {
        parent::__construct('interview', 'interviews.show');
    }
}
