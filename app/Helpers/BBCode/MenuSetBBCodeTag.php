<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class MenuSetBBCodeTag extends CodeDefinition
{
    public function __construct()
    {
        parent::__construct();
        $this->setTagName('menuSet');
        $this->setUseOption(true);
    }

    public function asHtml(ElementNode $el)
    {
        $content = '';
        foreach ($el->getChildren() as $child) {
            $content .= $child->getAsBBCode();
        }

        $attr = $el->getAttribute()['menuSet'];

        $menuSetId = explode('#', $attr)[0];
        $diskId = explode('#', $attr)[1] ?? '';
        $page = explode('#', $attr)[2] ?? 0;

        return join([
            '<a href="',
            route('menus.show', ['set' => $menuSetId, 'page' => $page]),
            "#menudisk-{$diskId}\">",
            $content,
            '</a>',
        ]);
    }
}
