<?php

namespace Tests\Unit\Rules;

use App\Rules\Slug;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    public function testGoodSlug()
    {
        $rule = new Slug;
        $this->assertTrue($rule->passes('test', '123-abc'));
    }

    public function testBadSlug()
    {
        $rule = new Slug;
        $this->assertFalse($rule->passes('test', 'XXX Test'));
    }

    public function testNumbersOnly()
    {
        $rule = new Slug;
        $this->assertFalse($rule->passes('test', '123'));
    }
}
