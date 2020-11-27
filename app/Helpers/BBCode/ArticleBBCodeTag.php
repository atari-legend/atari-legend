<?php

namespace App\Helpers\BBCode;

class ArticleBBCodeTag extends SectionBBCodeTag
{
    public function __construct()
    {
        parent::__construct('article', 'articles.show');
    }
}
