<?php

namespace Tests\Unit\Helpers;

use App\Helpers\MenuHelper;
use PHPUnit\Framework\TestCase;

class MenuHelperTest extends TestCase
{
    public function testPercentComplete()
    {
        $this->assertEquals(
            90,
            MenuHelper::percentComplete(100, 10)
        );

        $this->assertEquals(
            25,
            MenuHelper::percentComplete(100, 75)
        );
    }
}
