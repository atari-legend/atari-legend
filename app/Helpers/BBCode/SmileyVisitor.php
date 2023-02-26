<?php

namespace App\Helpers\BBCode;

use JBBCode\DocumentElement;
use JBBCode\ElementNode;
use JBBCode\NodeVisitor;
use JBBCode\TextNode;

/**
 * Replace Smileys in BBCode text.
 */
class SmileyVisitor implements NodeVisitor
{
    const SMILIES = [
        ':-D'       => 'icon_e_biggrin',
        ':)'        => 'icon_e_smile',
        ':('        => 'icon_e_sad',
        '8O'        => 'icon_eek',
        ':?'        => 'icon_e_confused',
        ' 8)'       => 'icon_cool',
        ':x'        => 'icon_mad',
        ':P'        => 'icon_razz',
        ':oops:'    => 'icon_redface',
        ':evil:'    => 'icon_evil',
        ':twisted:' => 'icon_twisted',
        ':roll:'    => 'icon_rolleyes',
        ':frown:'   => 'icon_frown',
        ':|'        => 'icon_neutral',
        ':mrgreen:' => 'icon_mrgreen',
        ':o'        => 'icon_e_surprised',
        ':lol:'     => 'icon_lol',
        ':cry:'     => 'icon_cry',
        ';)'        => 'icon_e_wink',
        ':wink:'    => 'icon_e_wink',
        ':!:'       => 'icon_exclaim',
        ':arrow:'   => 'icon_arrow',
        ':?:'       => 'icon_question',
        ':idea:'    => 'icon_idea',
    ];

    const IMG_EXT = 'gif';
    private string $imageUrlBase;

    public function __construct()
    {
        $this->imageUrlBase = asset('images/smilies/');
    }

    public function visitDocumentElement(DocumentElement $documentElement)
    {
        foreach ($documentElement->getChildren() as $child) {
            $child->accept($this);
        }
    }

    public function visitTextNode(TextNode $textNode)
    {
        foreach (SmileyVisitor::SMILIES as $text => $img) {
            $textNode->setValue(str_replace(
                $text,
                '<img class="border-0" style="height: 1rem;" src="'
                    . $this->imageUrlBase
                    . '/' . $img . '.' . SmileyVisitor::IMG_EXT
                    . '" alt="Smiley">',
                $textNode->getValue()
            ));
        }
    }

    public function visitElementNode(ElementNode $elementNode)
    {
        /* We only want to visit text nodes within elements if the element's
         * code definition allows for its content to be parsed.
         */
        if ($elementNode->getCodeDefinition()->parseContent()) {
            foreach ($elementNode->getChildren() as $child) {
                $child->accept($this);
            }
        }
    }
}
