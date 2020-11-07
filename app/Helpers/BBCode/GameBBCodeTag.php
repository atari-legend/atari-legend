<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class GameBBCodeTag extends CodeDefinition
{
    public function __construct()
    {
        parent::__construct();
        $this->setTagName('game');
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
            route('games.show', ['game' => $el->getAttribute()['game']]),
            '">',
            $content,
            '</a>',
        ]);
    }
}
