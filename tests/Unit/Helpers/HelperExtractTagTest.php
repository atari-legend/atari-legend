<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class HelperExtractTagTest extends TestCase
{
    public function testExtractNoTag()
    {
        $this->assertEquals(
            'No tag there',
            Helper::extractTag('No tag there', 'tag')
        );
    }

    public function testExtractTag()
    {
        $this->assertEquals(
            'a tag',
            Helper::extractTag('There is [tag]a tag[/tag] here', 'tag')
        );
    }

    public function testExtractPartialTag()
    {
        $this->assertEquals(
            'There is a partial [tag] here',
            Helper::extractTag('There is a partial [tag] here', 'tag')
        );
    }

    public function testExtractDoubleTag()
    {
        $this->assertEquals(
            'Double [tag]a tag[tag] here',
            Helper::extractTag('Double [tag]a tag[tag] here', 'tag')
        );
    }

    public function testExtractWithNewLines()
    {
        $this->assertEquals(
            'and a\ntag here',
            Helper::extractTag('Some\nNew lines\n[tag]and a\ntag here[/tag] more\nnew lines', 'tag')
        );
    }

    public function testExtractWithOption()
    {
        $this->assertEquals(
            'with options',
            Helper::extractTag('Some tag [tag=http://example.org/]with options[/tag]', 'tag')
        );
    }
}
