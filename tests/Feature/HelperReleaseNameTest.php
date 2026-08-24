<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Models\GameRelease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelperReleaseNameTest extends TestCase
{
    use RefreshDatabase;

    public function testNoDateNoName()
    {
        $release = new GameRelease();
        $this->assertEquals('[no date]', Helper::releaseName($release));
    }

    public function testNoDateName()
    {
        $release = new GameRelease();
        $release->name = 'Name';
        $this->assertEquals('[no date] Name', Helper::releaseName($release));
    }

    public function testDateNoName()
    {
        $release = new GameRelease();
        $release->date = 0;
        $this->assertEquals('1970', Helper::releaseName($release));
    }

    public function testDateName()
    {
        $release = new GameRelease();
        $release->date = 0;
        $release->name = 'Name';
        $this->assertEquals('1970 Name', Helper::releaseName($release));
    }
}
