<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DumpHelper;
use Tests\TestCase;

class DumpHelperTest extends TestCase
{
    public function testDetectFormat()
    {
        $this->assertEquals(
            'MSA',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.msa')
        );

        $this->assertEquals(
            'STX',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.stx')
        );

        $this->assertEquals(
            'SCP',
            DumpHelper::detectFormat(dirname(__FILE__) . '/example.scp')
        );

        $this->assertEquals(
            'ST',
            DumpHelper::detectFormat('/path/to/file.st')
        );

        $this->assertEquals(
            'OTHER',
            DumpHelper::detectFormat('/path/to/file.other')
        );
    }
}
