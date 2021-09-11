<?php

namespace App\Helpers\BBCode;

use JBBCode\CodeDefinition;
use JBBCode\ElementNode;

/**
 * A tag to run a search by id in one of the search fields
 * of the search page.
 */
class SearchByIdBBCodeTag extends CodeDefinition
{
    /**
     * Create a new search tag.
     *
     * @param  string  $name  Name of the tag, is also used as a search parameter
     *                        (for example '[developer=123]' will search for 'developer_id=123')
     */
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
