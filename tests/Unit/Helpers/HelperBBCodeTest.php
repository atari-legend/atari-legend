<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

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
}
