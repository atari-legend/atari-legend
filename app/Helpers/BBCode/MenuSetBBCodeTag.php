<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class MenuSetBBCodeTag extends CodeDefinition
{
    public function __construct(string $name = 'menuSet')
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

        $attr = $el->getAttribute()['menuSet'] ?? $el->getAttribute()['menuset'];

        $menuSetId = explode('#', $attr)[0];
        $diskId = explode('#', $attr)[1] ?? '';
        $page = explode('#', $attr)[2] ?? 1;

        return join([
            '<a href="',
            route('menus.show', ['set' => $menuSetId, 'page' => $page]),
            "#menudisk-{$diskId}\">",
            $content,
            '</a>',
        ]);
    }
}
