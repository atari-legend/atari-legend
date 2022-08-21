<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

class MagazineBBCodeTag extends CodeDefinition
{
    public function __construct(string $name = 'magazine')
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

        $attr = $el->getAttribute()['magazine'] ?? $el->getAttribute()['magazine'];

        $magazineId = explode('#', $attr)[0];
        $issueId = explode('#', $attr)[1] ?? '';
        $page = explode('#', $attr)[2] ?? 1;

        return join([
            '<a href="',
            route('magazines.show', ['magazine' => $magazineId, 'page' => $page]),
            "#magazine-issue-{$issueId}\">",
            $content,
            '</a>',
        ]);
    }
}
