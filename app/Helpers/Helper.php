<?php

namespace App\Helpers;

use App\Models\Release;
use App\Models\User;

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
        if (preg_match("@\\[$tag(=[^\\]]*)?\\](.*?)\\[/$tag\\]@s", $string, $matches,)) {
            return $matches[2];
        } else {
            return $string;
        }
    }

    /**
     * Return the name of a user.
     *
     * @param \App\Models\User $user Use to print the name of, or NULL
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
        $parser->addBBCode('hotspot', '<span id="{option}">{param}</span>', true);
        $parser->addBBCode('hotspotUrl', '<a href="{option}">{param}</a>', true);
        $parser->addBBCode('frontpage', '{param}');
        $parser->addBBCode('screenstar', '{param}');

        $parser->parse($bbCode);

        return $parser->getAsHtml();
    }


    public static function fileSize(int $size, string $unit = "")
    {
        if ((!$unit && $size >= 1 << 30) || $unit == "GB")
            return number_format($size / (1 << 30), 2) . " GB";
        if ((!$unit && $size >= 1 << 20) || $unit == "MB")
            return number_format($size / (1 << 20), 2) . " MB";
        if ((!$unit && $size >= 1 << 10) || $unit == "kB")
            return number_format($size / (1 << 10), 0) . " kB";
        return number_format($size) . " bytes";
    }

    public static function releaseName(Release $release)
    {
        $parts = [];
        if ($release->date !== null) {
            $parts[] = $release->date->year;
        } else {
            $parts[] = '[no date]';
        }

        if ($release->name !== null && $release->name !== '') {
            $parts[] = $release->name;
        }

        return join(' ', $parts);
    }
}
