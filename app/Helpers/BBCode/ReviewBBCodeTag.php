<?php

namespace App\Helpers\BBCode;

class ReviewBBCodeTag extends SectionBBCodeTag
{
    public function __construct()
    {
        parent::__construct('review', 'reviews.show');
    }
}
