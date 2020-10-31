<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Helper;
use PHPUnit\Framework\TestCase;

class HelperFileSizeTest extends TestCase
{
    public function testNoUnit()
    {
        $this->assertEquals('12 bytes', Helper::fileSize(12));
        $this->assertEquals('1 kB', Helper::fileSize(1234));
        $this->assertEquals('121 kB', Helper::fileSize(123456));
        $this->assertEquals('11.77 MB', Helper::fileSize(12345678));
    }

    public function testUnit()
    {
        $this->assertEquals('0.00 GB', Helper::fileSize(12, 'GB'));
        $this->assertEquals('0.00 GB', Helper::fileSize(1234, 'GB'));
        $this->assertEquals('0.12 MB', Helper::fileSize(123456, 'MB'));
        $this->assertEquals('12,056 kB', Helper::fileSize(12345678, 'kB'));
    }
}
