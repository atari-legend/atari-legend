<?php

namespace App\Helpers;

use App\User;

class Helper
{
    /**
     * Extract the content of a BB code tag.
     *
     * @param string $string String containing the tag to extract
     *
     * @return string Extracted content, or the input string if no content was extracted
     */
    public static function extractTag(string $string, string $tag)
    {
        if (preg_match("@\\[$tag(=[^\\]]*)?\\](.*?)\\[/$tag\\]@s", $string, $matches, )) {
            return $matches[2];
        } else {
            return $string;
        }
    }

    /**
     * Return the name of a user.
     *
     * @param \App\User $user Use to print the name of, or NULL
     *
     * @return string User name, or "Former user" if the user is NULL
     */
    public static function user(?User $user)
    {
        if (isset($user)) {
            return $user->userid;
        } else {
            return 'Former user';
        }
    }

    /**
     * Convert BBCode into HTML.
     *
     * @param string $bbcode BBCode string to convert
     *
     * @return string HTML conversion of the BBCode
     */
    public static function bbCode(?string $bbCode)
    {
        if ($bbCode === null) {
            return null;
        }

        $parser = new \JBBCode\Parser();
        $parser->addCodeDefinitionSet(new \JBBCode\DefaultCodeDefinitionSet());
        $parser->addBBCode('hotspot', '<a id="{option}">{param}</a>', true);
        $parser->addBBCode('hotspotUrl', '<a href="{option}">{param}</a>', true);
        $parser->addBBCode('frontpage', '{param}');
        $parser->addBBCode('screenstar', '{param}');

        $parser->parse($bbCode);

        return $parser->getAsHtml();
    }
}
