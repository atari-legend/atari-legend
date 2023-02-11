<?php

namespace Tests\Feature;

use App\Helpers\GameHelper;
use App\Models\Game;
use App\Models\Release;
use App\Models\ReleaseScan;
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
        $game->game_name = 'Test';
        $game->slug = 'test';
        $game->save();

        $release = new Release();
        $game->releases()->save($release);

        $scan = new ReleaseScan();
        $scan->imgext = 'png';
        $scan->type = 'Other';
        $release->boxscans()->save($scan);

        $this->assertTrue(GameHelper::hasBoxscan($game));
    }
}
