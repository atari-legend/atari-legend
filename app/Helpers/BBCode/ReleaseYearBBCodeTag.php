<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class ReleaseYearBBCodeTag extends CodeDefinition
{
    public function __construct()
    {
        parent::__construct();
        $this->setTagName('releaseYear');
    }

    public function asHtml(ElementNode $el)
    {
        $content = '';
        foreach ($el->getChildren() as $child) {
            $content .= $child->getAsBBCode();
        }

        return join([
            '<a href="',
            route('games.search', ['year' => $content]),
            '">',
            $content,
            '</a>',
        ]);
    }
}
