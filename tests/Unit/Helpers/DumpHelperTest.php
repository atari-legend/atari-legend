<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DumpHelper;
use Tests\TestCase;

class DumpHelperTest extends TestCase
{
    public function testDetectFormat()
    {
        $this->assertEquals(
            'msa',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.msa')
        );

        $this->assertEquals(
            'stx',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.stx')
        );

        $this->assertEquals(
            'scp',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.scp')
        );

        $this->assertEquals(
            'st',
            DumpHelper::detectFormat('/path/to/file.st')
        );

        $this->assertEquals(
            'other',
            DumpHelper::detectFormat('/path/to/file.other')
        );
    }
}
