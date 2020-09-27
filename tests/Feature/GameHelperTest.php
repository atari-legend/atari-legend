<?php

namespace Tests\Feature;

use App\Game;
use App\GameAka;
use App\Genre;
use App\Helpers\GameHelper;
use App\PublisherDeveloper;
use App\Release;
use App\ReleaseScan;
use App\Review;
use App\Screenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameHelperTest extends TestCase
{
    use RefreshDatabase;

    public function testEverything()
    {
        $game = new Game();
        $game->game_name = 'Name of the game';

        $game->save();

        $genre1 = new Genre();
        $genre1->name = 'Genre 1';
        $game->genres()->save($genre1);

        $genre2 = new Genre();
        $genre2->name = 'Genre 2';
        $game->genres()->save($genre2);

        $developer1 = new PublisherDeveloper();
        $developer1->pub_dev_name = 'Dev 1';
        $game->developers()->save($developer1);

        $developer2 = new PublisherDeveloper();
        $developer2->pub_dev_name = 'Dev 2';
        $game->developers()->save($developer2);

        $publisher1 = new PublisherDeveloper();
        $publisher1->pub_dev_name = 'Pub 1';
        $publisher1->save();

        $publisher2 = new PublisherDeveloper();
        $publisher2->pub_dev_name = 'Pub 2';
        $publisher2->save();

        $screenshot1 = new Screenshot();
        $screenshot1->imgext = 'png';
        $game->screenshots()->save($screenshot1);

        $game->reviews()->save(new Review());
        $game->reviews()->save(new Review());
        $game->reviews()->save(new Review());

        $aka1 = new GameAka();
        $aka1->aka_name = 'AKA 1';
        $game->akas()->save($aka1);

        $aka2 = new GameAka();
        $aka2->aka_name = 'AKA 2';
        $game->akas()->save($aka2);

        $release1 = new Release();
        $release1->date = '1988-03-02';
        $release1->publisher()->associate($publisher1);
        $game->releases()->save($release1);

        $scan1 = new ReleaseScan();
        $release1->boxscans()->save($scan1);

        $release2 = new Release();
        $release2->date = '1989-06-12';
        $release2->publisher()->associate($publisher2);
        $game->releases()->save($release2);

        $scan2 = new ReleaseScan();
        $release2->boxscans()->save($scan2);
        $scan3 = new ReleaseScan();
        $release2->boxscans()->save($scan3);

        $this->assertEquals(
            'Name of the game is a Genre 1, Genre 2 game for the Atari ST developed by Dev 1, Dev 2 released in 1988 (by Pub 1), 1989 (by Pub 2)'
                .' (2 releases, 3 boxscans, 1 screenshot, 3 reviews). It is also known as: AKA 1, AKA 2.',
            GameHelper::description($game)
        );
    }

    public function testSimple()
    {
        $game = new Game();
        $game->game_name = 'Name of the game';

        $game->save();

        $release1 = new Release();
        $release1->date = '1988-03-02';
        $game->releases()->save($release1);

        $scan1 = new ReleaseScan();
        $release1->boxscans()->save($scan1);

        $release2 = new Release();
        $game->releases()->save($release2);

        $this->assertEquals(
            'Name of the game is a game for the Atari ST released in 1988'
                .' (2 releases, 1 boxscan).',
            GameHelper::description($game)
        );
    }
}