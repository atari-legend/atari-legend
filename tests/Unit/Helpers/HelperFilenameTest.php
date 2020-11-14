<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class HelperFilenameTest extends TestCase
{
    public function test()
    {
        $this->assertEquals(null, Helper::filename(1, null));
        $this->assertEquals(null, Helper::filename(1, ''));
        $this->assertEquals(null, Helper::filename(1, '    '));
        $this->assertEquals('1.png', Helper::filename(1, 'png'));
    }
}
