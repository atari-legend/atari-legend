<?php

namespace Tests\Feature\Helpers;

use App\Helpers\StatisticsHelper;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Genre;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Models\MenuDisk;
use App\Models\PublisherDeveloper;
use App\Models\Review;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The figures on the public about page. Several of them count *games* through a
 * join table, so a game with two screenshots must still count once - which is
 * exactly what `distinct()` on a query builder does not always give you.
 */
class StatisticsHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_empty_database_reports_zero_everywhere(): void
    {
        $statistics = StatisticsHelper::getStatistics();

        // The SNDH archive ships with the migrations, so music is not zero
        unset($statistics['Music files']);

        foreach ($statistics as $label => $count) {
            $this->assertSame(0, $count, "Expected '{$label}' to be 0 on an empty database.");
        }
    }

    public function test_simple_counts_follow_the_tables(): void
    {
        Game::factory()->count(3)->create();
        GameRelease::factory()->count(2)->create();
        PublisherDeveloper::factory()->create();
        User::factory()->count(4)->create();
        Website::factory()->create();
        Magazine::factory()->create();
        MagazineIssue::factory()->count(2)->create();
        MenuDisk::factory()->create();

        $statistics = StatisticsHelper::getStatistics();

        // Each release brings its own game, each magazine issue its magazine,
        // and the link its submitting user
        $this->assertSame(5, $statistics['Games']);
        $this->assertSame(2, $statistics['Releases']);
        $this->assertSame(1, $statistics['Companies']);
        $this->assertSame(5, $statistics['Registered users']);
        $this->assertSame(1, $statistics['Links']);
        $this->assertSame(3, $statistics['Magazines']);
        $this->assertSame(2, $statistics['Magazine issues']);
        $this->assertSame(1, $statistics['Menu disks']);
        $this->assertSame(1, $statistics['Menu sets']);
    }

    /**
     * 'Screenshots' counts the pictures, 'Games with screenshots' the games -
     * so a game with two shots moves one figure by two and the other by one.
     */
    public function test_screenshots_and_games_with_screenshots_differ(): void
    {
        $game = Game::factory()->withScreenshot()->withScreenshot()->create();
        Game::factory()->create();

        $statistics = StatisticsHelper::getStatistics();

        $this->assertSame(2, $game->screenshots()->count());
        $this->assertSame(2, $statistics['Screenshots']);
        $this->assertSame(1, $statistics['Games with screenshots']);
    }

    public function test_reviewed_games_are_counted_once_per_game(): void
    {
        $game = Game::factory()->create();

        Review::factory()->forGame($game->getKey())->create();
        Review::factory()->forGame($game->getKey())->create();

        $this->assertSame(1, StatisticsHelper::getStatistics()['Games reviewed']);
    }

    public function test_games_with_a_company_come_from_their_releases(): void
    {
        GameRelease::factory()->publishedBy('Ocean')->create();
        GameRelease::factory()->create();

        $this->assertSame(1, StatisticsHelper::getStatistics()['Games with companies assigned']);
    }

    public function test_genres_are_counted_per_game(): void
    {
        $game = Game::factory()->create();

        DB::table('game_genre_cross')->insert(
            Genre::factory()->count(2)->create()
                ->map(fn (Genre $genre) => [
                    'game_id'       => $game->getKey(),
                    'game_genre_id' => $genre->getKey(),
                ])
                ->all()
        );

        $this->assertSame(1, StatisticsHelper::getStatistics()['Games with genre assigned']);
    }

    public function test_every_statistic_has_a_label(): void
    {
        foreach (array_keys(StatisticsHelper::getStatistics()) as $label) {
            $this->assertIsString($label);
            $this->assertNotSame('', $label);
        }
    }
}
