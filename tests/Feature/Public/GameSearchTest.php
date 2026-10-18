<?php

namespace Tests\Feature\Public;

use App\Models\Changelog;
use App\Models\Company;
use App\Models\Control;
use App\Models\Dump;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\GameSeries;
use App\Models\GameVideo;
use App\Models\GameVote;
use App\Models\Genre;
use App\Models\Individual;
use App\Models\MagazineIndex;
use App\Models\Media;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\ProgrammingLanguage;
use App\Models\Review;
use App\Models\Screenshot;
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
        return $this->search($query)->viewData('games')->pluck('name')->all();
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

    /**
     * A game with a screenshot and a dump.
     *
     * @return array The game, and the emulator page it plays on
     */
    private function dumpedGame(string $name): array
    {
        $game = Game::factory()->named($name)->create();
        $game->screenshots()->attach(Screenshot::factory()->create());
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);
        $media = Media::factory()->create(['game_release_id' => $release->getKey()]);
        $dump = Dump::factory()->create(['media_id' => $media->getKey()]);

        return [$game, route('games.releases.emulator', ['release' => $release, 'dump' => $dump])];
    }

    public function test_a_dumped_game_has_a_play_link(): void
    {
        [$game, $url] = $this->dumpedGame('Xenon');
        Game::factory()->named('Xenophobe')->create()->screenshots()->attach(Screenshot::factory()->create());

        $response = $this->search(['title' => 'Xen'])
            ->assertSee('href="' . $url . '" title="Play in the emulator"', false)
            ->assertSee(route('games.show', $game))
            ->assertDontSee('class="play-screenshot', false);

        $this->assertSame(1, substr_count($response->getContent(), 'title="Play in the emulator"'));
    }

    public function test_a_dumped_top_games_screenshot_opens_the_game(): void
    {
        [$game, $url] = $this->dumpedGame('Xenon');
        GameVote::factory()->create(['game_id' => $game->getKey()]);

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee(route('games.show', $game))
            ->assertDontSee($url);
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
            'game_id' => $game->getKey(),
            'name'    => 'Baburu Boburu',
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
        $developer = Company::factory()->create(['name' => 'The Bitmap Brothers']);
        $game->developers()->attach($developer, ['developer_role_id' => $this->developerRoleId()]);

        Game::factory()->named('Something else')->create();

        $this->assertSame(['Xenon'], $this->names(['developer' => 'Bitmap']));
        $this->assertSame(['Xenon'], $this->names(['developer_id' => $developer->getKey()]));
    }

    public function test_games_can_be_found_by_publisher(): void
    {
        $release = GameRelease::factory()->publishedBy('Ocean')->create();
        $game = $release->game;
        $game->update(['name' => 'Published game']);

        Game::factory()->named('Unpublished game')->create();

        $this->assertSame(['Published game'], $this->names(['publisher' => 'Ocean']));
        $this->assertSame(
            ['Published game'],
            $this->names(['publisher_id' => $release->fresh()->company_id])
        );
    }

    public function test_games_can_be_found_by_genre(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $genre = Genre::factory()->create(['name' => 'Shoot-em-up']);
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
        $release->game->update(['name' => 'From 1988']);

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
        $dump->media->release->game->update(['name' => 'Dumped']);

        Game::factory()->named('Not dumped')->create();

        $this->assertSame(['Dumped'], $this->names(['download' => 1]));
    }

    public function test_games_can_be_filtered_on_having_a_box_scan(): void
    {
        $release = GameRelease::factory()->create();
        $release->game->update(['name' => 'Scanned']);
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

    public function test_games_can_be_filtered_on_using_ai_or_not(): void
    {
        Game::factory()->named('Ported')->create(['uses_ai' => true]);
        Game::factory()->named('Hand made')->create();

        $this->assertSame(['Ported'], $this->names(['ai' => 1]));
        $this->assertSame(['Hand made'], $this->names(['ai' => 0]));
    }

    public function test_a_game_using_ai_carries_the_robot_in_the_results(): void
    {
        Game::factory()->named('Ported')->create(['uses_ai' => true]);
        Game::factory()->named('Hand made')->create();

        $this->search(['ai' => 1])->assertSee('fa-robot', false);
        $this->search(['ai' => 0])->assertDontSee('fa-robot', false);
    }

    /**
     * The dropdown's "-" posts an empty value, which is no criterion at all.
     */
    public function test_an_empty_ai_choice_is_not_a_criterion(): void
    {
        Game::factory()->named('Ported')->create(['uses_ai' => true]);

        $this->assertSame([], $this->names(['ai' => '']));
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
     * The results page lists what was searched on, with names rather than the
     * IDs the dropdowns post.
     */
    public function test_the_results_list_the_criteria(): void
    {
        Game::factory()->named('Xenon 1')->create();
        $genre = Genre::factory()->create(['name' => 'Shoot-em-up']);
        $developer = Company::factory()->create(['name' => 'The Bitmap Brothers']);

        $criteria = $this->search([
            'title'        => 'Xenon',
            'genre_id'     => $genre->getKey(),
            'developer_id' => $developer->getKey(),
            'ai'           => '0',
            'screenshot'   => 'on',
        ])
            ->assertSee('Shoot-em-up')
            ->assertSee('The Bitmap Brothers')
            ->viewData('criteria');

        $this->assertSame(
            [
                ['Title', 'Xenon'],
                ['Developer', 'The Bitmap Brothers'],
                ['Genre', 'Shoot-em-up'],
                ['Screenshot', true],
                ['Uses AI', false],
            ],
            array_map(fn ($c) => [$c['label'], $c['value']], $criteria)
        );
    }

    public function test_an_individual_criterion_carries_all_its_parameters(): void
    {
        $individual = Individual::factory()->create(['name' => 'Rob Hubbard']);

        $criteria = $this->search([
            'individual'        => 'Rob Hubbard (Xenon)',
            'individual_select' => $individual->getKey(),
        ])->viewData('criteria');

        $this->assertSame('Rob Hubbard', $criteria[0]['value']);
        $this->assertSame(['individual', 'individual_select', 'individual_id'], $criteria[0]['params']);
    }

    /**
     * Each criterion can be dropped on its own, and "Refine search" goes back
     * to the form with all of them.
     */
    public function test_the_results_link_to_refine_or_drop_criteria(): void
    {
        Game::factory()->named('Xenon 1')->create();

        $this->search(['title' => 'Xenon', 'screenshot' => 'on', 'page' => 2])
            ->assertSee(e(route('games.index', ['title' => 'Xenon', 'screenshot' => 'on'])), false)
            ->assertSee(e(route('games.search', ['screenshot' => 'on'])), false)
            ->assertSee(e(route('games.search', ['title' => 'Xenon'])), false);
    }

    /**
     * Dropping the last criterion would be a search for nothing, so it goes
     * back to the form instead.
     */
    public function test_dropping_the_last_criterion_goes_back_to_the_form(): void
    {
        Game::factory()->named('Xenon 1')->create();

        $this->assertMatchesRegularExpression(
            '/href="' . preg_quote(route('games.index'), '/') . '"\\s+class="btn btn-outline-secondary" title="Remove this criterion"/',
            $this->search(['title' => 'Xenon'])->getContent()
        );
    }

    /**
     * Whether the option of a dropdown is the one selected.
     */
    private function assertSelected(string $html, string $id, string $value): void
    {
        $this->assertMatchesRegularExpression(
            '/id="' . $id . '" name="' . $id . '"[^>]*>(?:(?!<\/select>).)*<option value="' . preg_quote($value, '/') . '"\s+selected>/s',
            $html,
            "{$id} should have {$value} selected"
        );
    }

    public function test_the_form_is_filled_in_from_the_query_string(): void
    {
        $genre = Genre::factory()->create(['name' => 'Shoot-em-up']);
        $developer = Company::factory()->create(['name' => 'The Bitmap Brothers']);
        $series = GameSeries::create(['name' => 'Xenon']);
        $language = ProgrammingLanguage::forceCreate(['name' => 'Assembly']);
        $control = Control::forceCreate(['name' => 'Joystick']);
        GameRelease::factory()->create(['date' => '1988-06-01']);
        GameRelease::factory()->create(['date' => '1990-06-01']);

        $html = $this->get(route('games.index', [
            'titleAZ'                 => 'X',
            'title'                   => 'Xenon',
            'publisher'               => 'Ocean',
            'genre_id'                => $genre->getKey(),
            'developer_id'            => $developer->getKey(),
            'year_from'               => '1988',
            'year_to'                 => '1990',
            'series_id'               => $series->getKey(),
            'programming_language_id' => $language->getKey(),
            'control_id'              => $control->getKey(),
            'players'                 => '2',
            'multiplayer'             => 'Turn by turn',
            'license'                 => 'Non-Commercial',
            'ai'                      => '0',
            'music'                   => 'on',
        ]))->assertOk()->getContent();

        $this->assertSelected($html, 'titleAZ', 'X');
        $this->assertStringContainsString('value="Xenon"', $html);
        $this->assertStringContainsString('value="Ocean"', $html);
        $this->assertSelected($html, 'genre_id', $genre->getKey());
        $this->assertSelected($html, 'developer_id', $developer->getKey());
        $this->assertSelected($html, 'year_from', '1988');
        $this->assertSelected($html, 'year_to', '1990');
        $this->assertSelected($html, 'series_id', $series->getKey());
        $this->assertSelected($html, 'programming_language_id', $language->getKey());
        $this->assertSelected($html, 'control_id', $control->getKey());
        $this->assertSelected($html, 'players', '2');
        $this->assertSelected($html, 'multiplayer', 'Turn by turn');
        $this->assertSelected($html, 'license', 'Non-Commercial');
        $this->assertSelected($html, 'ai', '0');
        // A legacy checkbox's "on" is a yes
        $this->assertSelected($html, 'music', '1');
        // The genre dropdown is shown in place of its free-text field
        $this->assertMatchesRegularExpression('/class="form-select\s+" id="genre_id"/', $html);
        $this->assertMatchesRegularExpression('/class="form-select\s+d-none\s+" id="engine_id"/', $html);
    }

    /**
     * Links elsewhere on the site search on a single year, which the form
     * shows as a range of one.
     */
    public function test_a_single_year_fills_in_the_form_as_a_range(): void
    {
        GameRelease::factory()->create(['date' => '1988-06-01']);

        foreach (['year', 'year_id'] as $param) {
            $html = $this->get(route('games.index', [$param => '1988']))->assertOk()->getContent();

            $this->assertSelected($html, 'year_from', '1988');
            $this->assertSelected($html, 'year_to', '1988');
        }
    }

    /**
     * The yes/no criteria search both ways, and links from before they were
     * dropdowns carried a checkbox's "on".
     */
    public function test_the_yes_no_criteria_find_the_games_without_too(): void
    {
        Game::factory()->named('With a shot')->withScreenshot()->create();
        Game::factory()->named('Without')->create();

        $this->assertSame(['With a shot'], $this->names(['screenshot' => 'on']));
        $this->assertSame(['Without'], $this->names(['screenshot' => '0']));
    }

    public function test_a_nested_yes_no_criterion_finds_the_games_without(): void
    {
        $dump = Dump::factory()->create();
        $dump->media->release->game->update(['name' => 'Dumped']);
        GameRelease::factory()->create()->game->update(['name' => 'Released, not dumped']);
        Game::factory()->named('Not released')->create();

        $this->assertSame(['Not released', 'Released, not dumped'], $this->names(['download' => '0']));
    }

    /**
     * A yes/no criterion offers the other answer as a link, keeping the rest
     * of the search.
     */
    public function test_a_yes_no_criterion_can_be_flipped_from_the_results(): void
    {
        Game::factory()->named('Xenon 1')->create();

        $this->search(['title' => 'Xenon', 'music' => '1', 'page' => 2])
            ->assertSee(e(route('games.search', ['title' => 'Xenon', 'music' => 0])), false)
            ->assertSee('<span class="btn btn-primary pe-none" aria-current="true">Yes</span>', false)
            ->assertSee('aria-label="Music: No"', false);
    }

    /**
     * A game is in the range when any of its releases is.
     */
    public function test_games_can_be_found_by_a_range_of_release_years(): void
    {
        foreach (['1986', '1988', '1990'] as $year) {
            GameRelease::factory()->create(['date' => "{$year}-06-01"])
                ->game->update(['name' => "From {$year}"]);
        }
        $both = GameRelease::factory()->create(['date' => '1984-01-01']);
        $both->game->update(['name' => 'From 1984 and 1989']);
        GameRelease::factory()->create(['game_id' => $both->game_id, 'date' => '1989-01-01']);

        $this->assertSame(
            ['From 1984 and 1989', 'From 1988'],
            $this->names(['year_from' => '1987', 'year_to' => '1989'])
        );
        $this->assertSame(
            ['From 1984 and 1989', 'From 1988', 'From 1990'],
            $this->names(['year_from' => '1988'])
        );
        $this->assertSame(
            ['From 1984 and 1989', 'From 1986'],
            $this->names(['year_to' => '1986'])
        );
    }

    public function test_a_range_of_years_is_described_in_the_criteria(): void
    {
        $describe = fn (array $query) => $this->search($query)->viewData('criteria')[0]['value'];

        $this->assertSame('1987–1989', $describe(['year_from' => '1987', 'year_to' => '1989']));
        $this->assertSame('1988', $describe(['year_from' => '1988', 'year_to' => '1988']));
        $this->assertSame('1988 or later', $describe(['year_from' => '1988']));
        $this->assertSame('1988 or earlier', $describe(['year_to' => '1988']));
        $this->assertSame(
            ['year_from', 'year_to'],
            $this->search(['year_from' => '1988'])->viewData('criteria')[0]['params']
        );
    }

    public function test_games_can_be_found_by_series(): void
    {
        $series = GameSeries::create(['name' => 'Xenon']);
        Game::factory()->named('Xenon')->create(['game_series_id' => $series->getKey()]);
        Game::factory()->named('Other')->create();

        $this->assertSame(['Xenon'], $this->names(['series_id' => $series->getKey()]));
    }

    public function test_games_can_be_found_by_programming_language(): void
    {
        $language = ProgrammingLanguage::forceCreate(['name' => 'STOS']);
        Game::factory()->named('Written in STOS')->create()->programmingLanguages()->attach($language);
        Game::factory()->named('Other')->create();

        $this->assertSame(['Written in STOS'], $this->names(['programming_language_id' => $language->getKey()]));
    }

    public function test_games_can_be_found_by_control(): void
    {
        $control = Control::forceCreate(['name' => 'Mouse']);
        Game::factory()->named('Pointed')->create()->controls()->attach($control);
        Game::factory()->named('Other')->create();

        $this->assertSame(['Pointed'], $this->names(['control_id' => $control->getKey()]));
    }

    /**
     * Players count on the same machine or across linked ones.
     */
    public function test_games_can_be_found_by_minimum_number_of_players(): void
    {
        Game::factory()->named('Two on one machine')->create(['players_same_machine' => 2]);
        Game::factory()->named('Four over a link')->create(['players_same_machine' => 1, 'players_multiple_machines' => 4]);
        Game::factory()->named('Single player')->create(['players_same_machine' => 1]);

        $this->assertSame(['Four over a link', 'Two on one machine'], $this->names(['players' => 2]));
        $this->assertSame(['Four over a link'], $this->names(['players' => 3]));
    }

    public function test_games_can_be_found_by_multiplayer_type(): void
    {
        Game::factory()->named('Together')->create(['multiplayer_type' => 'Simultaneous']);
        Game::factory()->named('In turn')->create(['multiplayer_type' => 'Turn by turn']);
        Game::factory()->named('Alone')->create();

        $this->assertSame(['In turn'], $this->names(['multiplayer' => 'Turn by turn']));
    }

    public function test_games_can_be_found_by_license(): void
    {
        GameRelease::factory()->create(['license' => GameRelease::LICENSE_NON_COMMERCIAL])
            ->game->update(['name' => 'Free']);
        GameRelease::factory()->create(['license' => GameRelease::LICENCE_COMMERCIAL])
            ->game->update(['name' => 'Paid']);

        $this->assertSame(['Free'], $this->names(['license' => 'Non-Commercial']));
        $this->assertSame(['Paid'], $this->names(['license' => 'Commercial']));
    }

    public function test_games_can_be_filtered_on_having_a_video(): void
    {
        $game = Game::factory()->named('Filmed')->create();
        GameVideo::create([
            'title'      => 'Longplay',
            'author'     => 'Someone',
            'youtube_id' => 'dQw4w9WgXcQ',
            'game_id'    => $game->getKey(),
        ]);
        Game::factory()->named('Unfilmed')->create();

        $this->assertSame(['Filmed'], $this->names(['video' => 1]));
        $this->assertSame(['Unfilmed'], $this->names(['video' => 0]));
    }

    public function test_games_can_be_filtered_on_magazine_coverage(): void
    {
        $index = MagazineIndex::factory()->forGame()->create();
        $index->game->update(['name' => 'Covered']);
        Game::factory()->named('Overlooked')->create();

        $this->assertSame(['Covered'], $this->names(['magazine' => 1]));
        $this->assertSame(['Overlooked'], $this->names(['magazine' => 0]));
    }

    /**
     * A game is on a menu disk through one of its releases, or directly when
     * the disk carries something about it (docs, a trainer, ...).
     */
    public function test_games_can_be_filtered_on_being_on_a_menu_disk(): void
    {
        $disk = MenuDisk::factory()->create();

        $release = GameRelease::factory()->create();
        $release->game->update(['name' => 'Through a release']);
        // forceCreate: game_release_id and game_id are outside MenuDiskContent::$fillable
        MenuDiskContent::forceCreate([
            'menu_disk_id'    => $disk->getKey(),
            'position'        => 1,
            'game_release_id' => $release->getKey(),
        ]);

        $direct = Game::factory()->named('Directly')->create();
        MenuDiskContent::forceCreate([
            'menu_disk_id' => $disk->getKey(),
            'position'     => 2,
            'game_id'      => $direct->getKey(),
        ]);

        GameRelease::factory()->create()->game->update(['name' => 'Not on a menu']);

        $this->assertSame(['Directly', 'Through a release'], $this->names(['menu' => 1]));
        $this->assertSame(['Not on a menu'], $this->names(['menu' => 0]));
    }

    /**
     * The new criteria show on the results with names rather than IDs, and
     * each is a game-only criterion that leaves software out.
     */
    public function test_the_new_criteria_are_listed_in_the_results(): void
    {
        $series = GameSeries::create(['name' => 'Xenon']);
        $language = ProgrammingLanguage::forceCreate(['name' => 'Assembly']);
        $control = Control::forceCreate(['name' => 'Joystick']);

        $response = $this->search([
            'series_id'               => $series->getKey(),
            'programming_language_id' => $language->getKey(),
            'control_id'              => $control->getKey(),
            'players'                 => '2',
            'multiplayer'             => 'Simultaneous',
            'download'                => '1',
            'license'                 => 'Commercial',
        ]);

        $this->assertSame(
            [
                ['Series', 'Xenon'],
                ['Programming language', 'Assembly'],
                ['Controls', 'Joystick'],
                ['Players', '2 or more'],
                ['Multiplayer', 'Simultaneous'],
                ['Downloadable / playable', true],
                ['License', 'Commercial'],
            ],
            array_map(fn ($c) => [$c['label'], $c['value']], $response->viewData('criteria'))
        );
    }

    public function test_the_new_criteria_leave_software_out(): void
    {
        \App\Models\MenuSoftware::factory()->named('Xtracker')->create();

        foreach (['year_from' => '1988', 'series_id' => 1, 'programming_language_id' => 1, 'control_id' => 1,
            'players'         => 2, 'multiplayer' => 'Simultaneous', 'license' => 'Commercial',
            'video'           => 1, 'magazine' => 1, 'menu' => 1, ] as $param => $value) {
            $this->assertCount(
                0,
                $this->search(['title' => 'Xtracker', $param => $value])->viewData('software'),
                $param
            );
        }
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
            'created_at'       => Carbon::now()->startOfMonth()->addDay(),
        ]);

        $updates = $this->get(route('games.index'))->assertOk()->viewData('updates');

        $this->assertCount(12, $updates);
        $this->assertSame(1, array_values($updates)[0]);
    }
}
