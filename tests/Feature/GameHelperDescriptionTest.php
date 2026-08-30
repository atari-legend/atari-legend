<?php

namespace Tests\Feature;

use App\Helpers\GameHelper;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\GameRelease;
use App\Models\GameGenre;
use App\Models\PubDev;
use App\Models\GameReleaseScan;
use App\Models\Review;
use App\Models\Screenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameHelperDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function testEverything()
    {
        $game = new Game();
        $game->game_name = 'Name of the game';
        $game->slug = 'slug';

        $game->save();

        $genre1 = new GameGenre();
        $genre1->name = 'Genre 1';
        $game->genres()->save($genre1);

        $genre2 = new GameGenre();
        $genre2->name = 'Genre 2';
        $game->genres()->save($genre2);

        $developer1 = new PubDev();
        $developer1->pub_dev_name = 'Dev 1';
        $game->developers()->save($developer1);

        $developer2 = new PubDev();
        $developer2->pub_dev_name = 'Dev 2';
        $game->developers()->save($developer2);

        $publisher1 = new PubDev();
        $publisher1->pub_dev_name = 'Pub 1';
        $publisher1->save();

        $publisher2 = new PubDev();
        $publisher2->pub_dev_name = 'Pub 2';
        $publisher2->save();

        $screenshot1 = new Screenshot();
        $screenshot1->imgext = 'png';
        $game->screenshots()->save($screenshot1);

        $game->reviews()->save(new Review(['review_text' => '', 'review_date' => now()]));
        $game->reviews()->save(new Review(['review_text' => '', 'review_date' => now()]));
        $game->reviews()->save(new Review(['review_text' => '', 'review_date' => now()]));

        $aka1 = new GameAka();
        $aka1->aka_name = 'AKA 1';
        $game->akas()->save($aka1);

        $aka2 = new GameAka();
        $aka2->aka_name = 'AKA 2';
        $game->akas()->save($aka2);

        $release1 = new GameRelease();
        $release1->date = '1988-03-02';
        $release1->publisher()->associate($publisher1);
        $game->releases()->save($release1);

        $scan1 = new GameReleaseScan();
        $scan1->imgext = 'png';
        $scan1->type = 'Other';
        $release1->boxscans()->save($scan1);

        $release2 = new GameRelease();
        $release2->date = '1989-06-12';
        $release2->publisher()->associate($publisher2);
        $game->releases()->save($release2);

        $scan2 = new GameReleaseScan();
        $scan2->imgext = 'png';
        $scan2->type = 'Other';
        $release2->boxscans()->save($scan2);
        $scan3 = new GameReleaseScan();
        $scan3->imgext = 'png';
        $scan3->type = 'Other';
        $release2->boxscans()->save($scan3);

        $this->assertEquals(
            'Name of the game is a Genre 1, Genre 2 game for the Atari ST developed by Dev 1, Dev 2 released in 1988 (by Pub 1), 1989 (by Pub 2)'
                . ' (2 releases, 3 boxscans, 1 screenshot, 3 reviews). It is also known as: AKA 1, AKA 2.',
            GameHelper::description($game)
        );
    }

    public function testSimple()
    {
        $game = new Game();
        $game->game_name = 'Name of the game';
        $game->slug = 'slug';

        $game->save();

        $release1 = new GameRelease();
        $release1->date = '1988-03-02';
        $game->releases()->save($release1);

        $scan1 = new GameReleaseScan();
        $scan1->imgext = 'png';
        $scan1->type = 'Other';
        $release1->boxscans()->save($scan1);

        $release2 = new GameRelease();
        $game->releases()->save($release2);

        $this->assertEquals(
            'Name of the game is a game for the Atari ST released in 1988'
                . ' (2 releases, 1 boxscan).',
            GameHelper::description($game)
        );
    }
}
