<?php

namespace Tests\Feature;

use App\Helpers\GameHelper;
use App\Models\Game;
use App\Models\PublisherDeveloper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameHelperHasDevelopersTest extends TestCase
{
    use RefreshDatabase;

    public function testNoDevelopers()
    {
        $game = new Game();

        $this->assertEquals('', GameHelper::developers($game));
    }

    public function testDevelopers()
    {
        $game = new Game();
        $game->game_name = 'Test';
        $game->save();

        $dev1 = new PublisherDeveloper();
        $dev1->pub_dev_name = 'Dev 1';
        $dev1->save();
        $game->developers()->save($dev1);

        $dev2 = new PublisherDeveloper();
        $dev2->pub_dev_name = 'Dev 2';
        $dev2->save();
        $game->developers()->save($dev2);

        $this->assertEquals('Dev 1, Dev 2', GameHelper::developers($game));
    }
}
