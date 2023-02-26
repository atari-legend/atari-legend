<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use Tests\TestCase;

class HelperBBCodeTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(
            '<a href="https://example.org">an example</a>',
            Helper::bbCode('[url=https://example.org]an example[/url]')
        );
    }

    public function testNull()
    {
        $this->assertEquals(
            null,
            Helper::bbCode(null)
        );
    }

    public function testEmptyString()
    {
        $this->assertEquals(
            '',
            Helper::bbCode('')
        );
    }

    public function testSmiley()
    {
        $this->assertEquals(
            'Some text <img class="border-0" style="height: 1rem;" src="http://localhost/images/smilies/icon_redface.gif" alt="Smiley"> and more',
            Helper::bbCode('Some text :oops: and more')
        );
    }
}
