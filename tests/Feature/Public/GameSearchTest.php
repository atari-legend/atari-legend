<?php

namespace Tests\Feature\Public;

use App\Models\Changelog;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameGenre;
use App\Models\Individual;
use App\Models\PubDev;
use App\Models\Review;
use App\Models\Sndh;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The game search. Every criterion is optional and they combine, but the two
 * rules that are easy to break are: with no criteria at all the search returns
 * nothing rather than everything, and software is only searchable by title.
 *
 * The SQL dialect side of this is covered by SearchDialectTest; this is about
 * the filtering itself.
 */
class GameSearchTest extends TestCase
{
    use RefreshDatabase;

    private function search(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('games.search', $query))->assertOk();
    }

    private function names(array $query): array
    {
        return $this->search($query)->viewData('games')->pluck('game_name')->all();
    }

    private function developerRoleId(): int
    {
        return DB::table('developer_roles')->insertGetId(['name' => 'Developer']);
    }

    public function test_the_landing_page_counts_the_games(): void
    {
        Game::factory()->count(3)->create();

        $this->assertSame(3, $this->get(route('games.index'))->assertOk()->viewData('gamesCount'));
    }

    /**
     * A search with nothing filled in must not dump the whole database.
     */
    public function test_a_search_with_no_criteria_returns_nothing(): void
    {
        Game::factory()->count(3)->create();

        $this->assertSame([], $this->names([]));
    }

    public function test_games_are_matched_on_part_of_the_title(): void
    {
        Game::factory()->named('Bubble Bobble')->create();
        Game::factory()->named('Rick Dangerous')->create();

        $this->assertSame(['Bubble Bobble'], $this->names(['title' => 'Bobble']));
    }

    public function test_alternative_titles_are_searched(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();
        DB::table('game_akas')->insert([
            'game_id'  => $game->getKey(),
            'aka_name' => 'Baburu Boburu',
        ]);

        $this->assertSame(['Bubble Bobble'], $this->names(['title' => 'Baburu']));
    }

    /**
     * An exact single match skips the results page and goes straight to the
     * game.
     */
    public function test_an_exact_single_match_redirects_to_the_game(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->get(route('games.search', ['title' => 'xenon']))
            ->assertRedirect(route('games.show', $game));
    }

    public function test_an_exact_match_alongside_others_still_lists_them(): void
    {
        Game::factory()->named('Xenon')->create();
        Game::factory()->named('Xenon 2')->create();

        $this->assertSame(['Xenon', 'Xenon 2'], $this->names(['title' => 'Xenon']));
    }

    public function test_games_can_be_found_by_developer(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $developer = PubDev::factory()->create(['pub_dev_name' => 'The Bitmap Brothers']);
        $game->developers()->attach($developer, ['developer_role_id' => $this->developerRoleId()]);

        Game::factory()->named('Something else')->create();

        $this->assertSame(['Xenon'], $this->names(['developer' => 'Bitmap']));
        $this->assertSame(['Xenon'], $this->names(['developer_id' => $developer->getKey()]));
    }

    public function test_games_can_be_found_by_publisher(): void
    {
        $release = GameRelease::factory()->publishedBy('Ocean')->create();
        $game = $release->game;
        $game->update(['game_name' => 'Published game']);

        Game::factory()->named('Unpublished game')->create();

        $this->assertSame(['Published game'], $this->names(['publisher' => 'Ocean']));
        $this->assertSame(
            ['Published game'],
            $this->names(['publisher_id' => $release->fresh()->pub_dev_id])
        );
    }

    public function test_games_can_be_found_by_genre(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $genre = GameGenre::factory()->create(['name' => 'Shoot-em-up']);
        $game->genres()->attach($genre);

        Game::factory()->named('Other')->create();

        $this->assertSame(['Xenon'], $this->names(['genre' => 'Shoot']));
        $this->assertSame(['Xenon'], $this->names(['genre_id' => $genre->getKey()]));
    }

    public function test_games_can_be_found_by_engine(): void
    {
        $game = Game::factory()->named('Adventure')->create();
        $engine = Engine::forceCreate(['name' => 'STOS']);
        $game->engines()->attach($engine);

        Game::factory()->named('Other')->create();

        $this->assertSame(['Adventure'], $this->names(['engine' => 'STOS']));
        $this->assertSame(['Adventure'], $this->names(['engine_id' => $engine->getKey()]));
    }

    public function test_games_can_be_found_by_release_year(): void
    {
        $release = GameRelease::factory()->create(['date' => '1988-06-01']);
        $release->game->update(['game_name' => 'From 1988']);

        GameRelease::factory()->create(['date' => '1992-01-01']);

        $this->assertSame(['From 1988'], $this->names(['year' => '1988']));
        $this->assertSame(['From 1988'], $this->names(['year_id' => '1988']));
    }

    /**
     * Searching for someone has to find games credited to any of their
     * nicknames as well as to the name itself.
     */
    public function test_an_individual_search_covers_their_nicknames(): void
    {
        $roleId = DB::table('individual_roles')->insertGetId(['name' => 'Coder']);

        $individual = Individual::factory()->nicknamed('Mr X')->create();
        $nickname = $individual->nicknames->first();

        $creditedToName = Game::factory()->named('Credited to the name')->create();
        $creditedToName->individuals()->attach($individual, ['individual_role_id' => $roleId]);

        $creditedToNick = Game::factory()->named('Credited to the nick')->create();
        $creditedToNick->individuals()->attach($nickname, ['individual_role_id' => $roleId]);

        Game::factory()->named('Not credited')->create();

        $this->assertSame(
            ['Credited to the name', 'Credited to the nick'],
            $this->names(['individual_id' => $individual->getKey()])
        );
    }

    public function test_an_unknown_individual_matches_nothing_in_particular(): void
    {
        Game::factory()->named('Xenon')->create();

        // The criterion still counts as a search, so everything comes back
        $this->assertSame(['Xenon'], $this->names(['individual_id' => 9999]));
    }

    public function test_games_can_be_filtered_on_having_a_review(): void
    {
        $reviewed = Game::factory()->named('Reviewed')->create();
        Review::factory()->forGame($reviewed->getKey())->create();

        Game::factory()->named('Unreviewed')->create();

        $this->assertSame(['Reviewed'], $this->names(['review' => 1]));
    }

    public function test_games_can_be_filtered_on_having_a_screenshot(): void
    {
        Game::factory()->named('With a shot')->withScreenshot()->create();
        Game::factory()->named('Without')->create();

        $this->assertSame(['With a shot'], $this->names(['screenshot' => 1]));
    }

    public function test_games_can_be_filtered_on_having_a_download(): void
    {
        $dump = \App\Models\Dump::factory()->create();
        $dump->media->release->game->update(['game_name' => 'Dumped']);

        Game::factory()->named('Not dumped')->create();

        $this->assertSame(['Dumped'], $this->names(['download' => 1]));
    }

    public function test_games_can_be_filtered_on_having_a_box_scan(): void
    {
        $release = GameRelease::factory()->create();
        $release->game->update(['game_name' => 'Scanned']);
        DB::table('game_release_scans')->insert([
            'game_release_id' => $release->getKey(),
            'type'            => 'Box front',
            'imgext'          => 'jpg',
        ]);

        Game::factory()->named('Unscanned')->create();

        $this->assertSame(['Scanned'], $this->names(['boxscan' => 1]));
    }

    public function test_games_can_be_filtered_on_having_music(): void
    {
        $game = Game::factory()->named('With music')->create();
        $sndh = Sndh::factory()->create();
        $game->sndhs()->attach($sndh);

        Game::factory()->named('Silent')->create();

        $this->assertSame(['With music'], $this->names(['music' => 1]));
    }

    /**
     * Criteria narrow each other rather than widening the result.
     *
     * Neither title here matches the term exactly, which would otherwise
     * trigger the redirect-to-the-single-match shortcut.
     */
    public function test_criteria_combine(): void
    {
        Game::factory()->named('Xenon 1')->withScreenshot()->create();
        Game::factory()->named('Xenon 2')->create();

        $this->assertSame(['Xenon 1'], $this->names(['title' => 'Xenon', 'screenshot' => 1]));
        $this->assertSame(['Xenon 1', 'Xenon 2'], $this->names(['title' => 'Xenon']));
    }

    public function test_results_are_paginated(): void
    {
        foreach (range(1, 50) as $i) {
            Game::factory()->named('Xenon ' . $i)->create();
        }

        $games = $this->search(['title' => 'Xenon'])->viewData('games');

        $this->assertCount(48, $games);
        $this->assertSame(50, $games->total());
    }

    /**
     * The export view needs the whole result set, so pagination is skipped.
     */
    public function test_an_export_returns_every_match_unpaginated(): void
    {
        foreach (range(1, 50) as $i) {
            Game::factory()->named('Xenon ' . $i)->create();
        }

        $response = $this->search(['title' => 'Xenon', 'export' => 1]);

        $this->assertCount(50, $response->viewData('games'));
        $this->assertTrue($response->viewData('export'));
    }

    /**
     * Software is only reachable by title; picking any game-specific criterion
     * takes it out of the results.
     */
    public function test_software_is_only_searchable_by_title(): void
    {
        \App\Models\MenuSoftware::factory()->named('Xtracker')->create();

        $this->assertSame(
            ['Xtracker'],
            $this->search(['title' => 'Xtracker'])->viewData('software')->pluck('name')->all()
        );

        $this->assertCount(
            0,
            $this->search(['title' => 'Xtracker', 'screenshot' => 1])->viewData('software')
        );
    }

    /**
     * The landing page shows a year of activity taken from the changelog, one
     * bucket per month.
     */
    public function test_the_landing_page_reports_updates_by_month(): void
    {
        Changelog::create([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => 1,
            'section_name'     => 'Xenon',
            'sub_section'      => '',
            'sub_section_id'   => 0,
            'sub_section_name' => '',
            'user_id'          => 1,
            'timestamp'        => Carbon::now()->startOfMonth()->addDay()->timestamp,
        ]);

        $updates = $this->get(route('games.index'))->assertOk()->viewData('updates');

        $this->assertCount(12, $updates);
        $this->assertSame(1, array_values($updates)[0]);
    }
}
