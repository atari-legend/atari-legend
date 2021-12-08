<?php

namespace App\Helpers;

use App\Helpers\BBCode\ArticleBBCodeTag;
use App\Helpers\BBCode\GameBBCodeTag;
use App\Helpers\BBCode\InterviewBBCodeTag;
use App\Helpers\BBCode\MenuSetBBCodeTag;
use App\Helpers\BBCode\ReleaseYearBBCodeTag;
use App\Helpers\BBCode\ReviewBBCodeTag;
use App\Helpers\BBCode\SearchByIdBBCodeTag;
use App\Helpers\BBCode\SmileyVisitor;
use App\Models\Release;
use App\Models\User;

class Helper
{
    /**
     * Extract the content of a BB code tag.
     *
     * @param  string  $string  String containing the tag to extract
     * @return string Extracted content, or the input string if no content was extracted
     */
    public static function extractTag(?string $string, string $tag)
    {
        if ($string === null) {
            return null;
        }

        if (preg_match("@\\[$tag(=[^\\]]*)?\\](.*?)\\[/$tag\\]@s", $string, $matches, )) {
            return $matches[2];
        } else {
            return $string;
        }
    }

    /**
     * Return the name of a user.
     *
     * @param  \App\Models\User  $user  Use to print the name of, or NULL
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
     * @param  string  $bbcode  BBCode string to convert
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
        $parser->addCodeDefinition(new GameBBCodeTag());
        $parser->addCodeDefinition(new InterviewBBCodeTag());
        $parser->addCodeDefinition(new ReviewBBCodeTag());
        $parser->addCodeDefinition(new ArticleBBCodeTag());
        $parser->addCodeDefinition(new SearchByIdBBCodeTag('publisher'));
        $parser->addCodeDefinition(new SearchByIdBBCodeTag('developer'));
        $parser->addCodeDefinition(new SearchByIdBBCodeTag('individual'));
        $parser->addCodeDefinition(new ReleaseYearBBCodeTag());
        $parser->addCodeDefinition(new MenuSetBBCodeTag());

        $parser->parse($bbCode);

        $parser->accept(new SmileyVisitor());

        return $parser->getAsHtml();
    }

    /**
     * Format a file size number into kB / MB / GB.
     *
     * @param  int  $size  File size to format
     * @param  string  $unit  Desired unit, Pass an empty string to
     *                        have the unit chosen automatically
     * @return string Formatted number
     */
    public static function fileSize(int $size, string $unit = '')
    {
        if ((! $unit && $size >= 1 << 30) || $unit == 'GB') {
            return number_format($size / (1 << 30), 2).' GB';
        }
        if ((! $unit && $size >= 1 << 20) || $unit == 'MB') {
            return number_format($size / (1 << 20), 2).' MB';
        }
        if ((! $unit && $size >= 1 << 10) || $unit == 'kB') {
            return number_format($size / (1 << 10), 0).' kB';
        }

        return number_format($size).' bytes';
    }

    /**
     * Get the year + name of a release.
     *
     * @param  \App\Models\Release  $release  Release to get the year + name of
     * @return string Year + name of the release
     */
    public static function releaseName(Release $release)
    {
        $parts = [$release->year];

        if ($release->name !== null && $release->name !== '') {
            $parts[] = $release->name;
        }

        return join(' ', $parts);
    }

    /**
     * Get a filename from an ID and an image extension. Intended to be used
     * to generate filenames out of the database information, where the image
     * extension is sometimes missing or set blank.
     *
     * @param  int  $id  Identifier, used as the file name
     * @param  string  $imgext  Extension, possibly null
     * @return string Filename, or NULL if there's no file extension
     */
    public static function filename(int $id, ?string $imgext)
    {
        if ($imgext !== null && trim($imgext) !== '') {
            return $id.'.'.$imgext;
        } else {
            return null;
        }
    }
}
