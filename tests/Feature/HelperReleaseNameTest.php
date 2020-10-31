<?php

namespace Tests\Feature;

use App\Helpers\Helper;
use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelperReleaseName extends TestCase
{
    use RefreshDatabase;

    public function testNoDateNoName()
    {
        $release = new Release();
        $this->assertEquals('[no date]', Helper::releaseName($release));
    }

    public function testNoDateName()
    {
        $release = new Release();
        $release->name = 'Name';
        $this->assertEquals('[no date] Name', Helper::releaseName($release));
    }

    public function testDateNoName()
    {
        $release = new Release();
        $release->date = 0;
        $this->assertEquals('1970', Helper::releaseName($release));
    }

    public function testDateName()
    {
        $release = new Release();
        $release->date = 0;
        $release->name = 'Name';
        $this->assertEquals('1970 Name', Helper::releaseName($release));
    }
}
