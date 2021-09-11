<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

/**
 * Base class for BBCode tags pointing to a specific section
 * like games, interviews, reviews, articles.
 */
abstract class SectionBBCodeTag extends CodeDefinition
{
    private string $route;

    /**
     * @param  string  $tag  Name of the tag (e.g. 'game')
     * @param  string  $route  Route to use when building the link to the item
     *                         pointed by the tag (e.g. 'games.show')
     */
    public function __construct(string $tag, string $route)
    {
        parent::__construct();
        $this->setTagName($tag);
        $this->setUseOption(true);
        $this->route = $route;
    }

    public function asHtml(ElementNode $el)
    {
        $content = '';
        foreach ($el->getChildren() as $child) {
            $content .= $child->getAsBBCode();
        }

        return join([
            '<a href="',
            route($this->route, [$this->tagName => $el->getAttribute()[$this->tagName]]),
            '">',
            $content,
            '</a>',
        ]);
    }
}
