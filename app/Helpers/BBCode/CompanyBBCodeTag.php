<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class CompanyBBCodeTag extends CodeDefinition
{
    public function __construct(string $name)
    {
        parent::__construct();
        $this->setTagName($name);
        $this->setUseOption(true);
    }

    public function asHtml(ElementNode $el)
    {
        $content = '';
        foreach ($el->getChildren() as $child) {
            $content .= $child->getAsBBCode();
        }

        return join([
            '<a href="',
            route('games.search', [$this->getTagName().'_id' => $el->getAttribute()[$this->getTagName()]]),
            '">',
            $content,
            '</a>',
        ]);
    }
}
