<?php

namespace Tests\Feature;

use App\Helpers\GameHelper;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameReleaseScan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameHelperHasBoxscanTest extends TestCase
{
    use RefreshDatabase;

    public function testNoBoxscan()
    {
        $game = new Game();
        $this->assertFalse(GameHelper::hasBoxscan($game));
    }

    public function testBoxscanReleaseLevel()
    {
        $game = new Game();
        $game->name = 'Test';
        $game->slug = 'test';
        $game->save();

        $release = new GameRelease();
        $game->releases()->save($release);

        $scan = new GameReleaseScan();
        $scan->imgext = 'png';
        $scan->type = 'Other';
        $release->boxscans()->save($scan);

        $this->assertTrue(GameHelper::hasBoxscan($game));
    }
}
