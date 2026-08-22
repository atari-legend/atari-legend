<?php

namespace Tests\Feature\Public;

use App\Models\Game;
use App\Models\MenuSoftware;
use App\Models\PublisherDeveloper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The autocomplete endpoints behind the search boxes.
 *
 * These used to order on LOCATE() and build their URLs with CONCAT(), both
 * MySQL-only, which is why they had no tests at all. They now use instr() and
 * length(), which MySQL and SQLite spell the same way, so this runs against the
 * same SQLite connection as the rest of the suite.
 */
class AutocompleteTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $name): Game
    {
        return Game::factory()->named($name)->create();
    }

    private function aka(Game $game, string $name): void
    {
        DB::table('game_aka')->insert([
            'game_id'  => $game->getKey(),
            'aka_name' => $name,
        ]);
    }

    private function names(array $results, string $key = 'game_name'): array
    {
        return array_column($results, $key);
    }

    public function test_games_are_matched_anywhere_in_the_title(): void
    {
        $this->game('Bubble Bobble');
        $this->game('Rick Dangerous');

        $results = $this->getJson(route('ajax.games', ['q' => 'obble']))
            ->assertOk()
            ->json();

        $this->assertSame(['Bubble Bobble'], $this->names($results));
    }

    /**
     * A title that starts with the term beats one that merely contains it -
     * the ordering instr() provides.
     */
    public function test_earlier_matches_come_first(): void
    {
        $this->game('Super Xenon');
        $this->game('Xenon 2');

        $results = $this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json();

        $this->assertSame(['Xenon 2', 'Super Xenon'], $this->names($results));
    }

    /**
     * Both endpoints feed a search box, so they must rank the same titles the
     * same way: earliest match first, then shortest, then alphabetically.
     *
     * They used to disagree - games.json ranked on match position alone. The
     * length tie-break existed in games-and-software.json but did nothing,
     * because CHAR_LENGTH('game_name') measured the quoted literal rather than
     * the column.
     */
    public function test_both_endpoints_rank_titles_the_same_way(): void
    {
        foreach (['Super Xenon', 'Xenon 2 Megablast', 'Xenon'] as $name) {
            $this->game($name);
        }

        $expected = ['Xenon', 'Xenon 2 Megablast', 'Super Xenon'];

        $this->assertSame(
            $expected,
            $this->names($this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json())
        );

        $this->assertSame(
            $expected,
            $this->names(
                $this->getJson(route('ajax.games-and-software', ['q' => 'Xenon']))->assertOk()->json(),
                'name'
            )
        );
    }

    /**
     * Titles matching at the same position are ordered by length, so the
     * shortest - usually the one being looked for - comes first.
     */
    public function test_equally_good_matches_are_ordered_shortest_first(): void
    {
        foreach (['Xenon 2 Megablast', 'Xenon', 'Xenon 2'] as $name) {
            $this->game($name);
        }

        $this->assertSame(
            ['Xenon', 'Xenon 2', 'Xenon 2 Megablast'],
            $this->names($this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json())
        );
    }

    /**
     * Titles of the same length fall back to alphabetical order, so the list
     * is not left to whatever order the rows came back in.
     */
    public function test_titles_of_the_same_length_are_alphabetical(): void
    {
        foreach (['Xenon B', 'Xenon A'] as $name) {
            $this->game($name);
        }

        $this->assertSame(
            ['Xenon A', 'Xenon B'],
            $this->names($this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json())
        );
    }

    public function test_alternative_titles_are_searched_too(): void
    {
        $game = $this->game('Bubble Bobble');
        $this->aka($game, 'Baburu Boburu');

        $results = $this->getJson(route('ajax.games', ['q' => 'Baburu']))->assertOk()->json();

        $this->assertSame(['Baburu Boburu'], $this->names($results));
        $this->assertSame($game->getKey(), $results[0]['game_id']);
    }

    /**
     * The URL used to be built by CONCAT() in the query; it now comes from the
     * route helper, and has to keep pointing at the same page.
     */
    public function test_each_result_carries_a_link_to_the_game(): void
    {
        $game = $this->game('Xenon');

        $results = $this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json();

        $this->assertSame(route('games.show', $game->getKey()), $results[0]['url']);
    }

    /**
     * With no term there is nothing to rank against, so both endpoints fall
     * back to alphabetical order rather than to the shortest title.
     */
    public function test_no_query_lists_everything_alphabetically(): void
    {
        $this->game('Zynaps');
        $this->game('Arkanoid');

        $this->assertSame(
            ['Arkanoid', 'Zynaps'],
            $this->names($this->getJson(route('ajax.games'))->assertOk()->json())
        );

        $this->assertSame(
            ['Arkanoid', 'Zynaps'],
            $this->names($this->getJson(route('ajax.games-and-software'))->assertOk()->json(), 'name')
        );
    }

    public function test_at_most_ten_games_come_back(): void
    {
        foreach (range(1, 15) as $i) {
            $this->game('Xenon ' . $i);
        }

        $this->assertCount(10, $this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json());
    }

    public function test_nothing_matching_gives_an_empty_list(): void
    {
        $this->game('Xenon');

        $this->assertSame([], $this->getJson(route('ajax.games', ['q' => 'Llamatron']))->assertOk()->json());
    }

    /**
     * The term is bound rather than pasted into the SQL. A quote used to end
     * the string literal it was being concatenated into.
     */
    public function test_a_quote_in_the_term_is_not_treated_as_sql(): void
    {
        $this->game("Mickey's Land");

        $results = $this->getJson(route('ajax.games', ['q' => "Mickey's"]))->assertOk()->json();

        $this->assertSame(["Mickey's Land"], $this->names($results));
    }

    public function test_the_combined_search_finds_games_and_software(): void
    {
        $this->game('Xenon');
        MenuSoftware::factory()->named('Xtracker')->create();

        $results = $this->getJson(route('ajax.games-and-software', ['q' => 'X']))->assertOk()->json();

        $this->assertEqualsCanonicalizing(['Xenon', 'Xtracker'], $this->names($results, 'name'));
    }

    /**
     * Games and software are told apart by their icon, and each links into its
     * own section.
     */
    public function test_the_combined_search_links_each_kind_to_its_own_page(): void
    {
        $game = $this->game('Xenon');
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $results = collect($this->getJson(route('ajax.games-and-software', ['q' => 'X']))->assertOk()->json())
            ->keyBy('name');

        $this->assertSame('fa-gamepad', $results['Xenon']['icon']);
        $this->assertSame(route('games.show', $game->getKey()), $results['Xenon']['url']);

        $this->assertSame('fa-desktop', $results['Xtracker']['icon']);
        $this->assertSame(route('menus.software', $software->getKey()), $results['Xtracker']['url']);
    }

    public function test_the_combined_search_prefers_earlier_then_shorter_matches(): void
    {
        $this->game('Super Xenon');
        $this->game('Xenon 2 Megablast');
        $this->game('Xenon');

        $results = $this->getJson(route('ajax.games-and-software', ['q' => 'Xenon']))->assertOk()->json();

        $this->assertSame(
            ['Xenon', 'Xenon 2 Megablast', 'Super Xenon'],
            $this->names($results, 'name')
        );
    }

    /**
     * The admin autocomplete annotates each game with its developers, and is
     * only reachable by an administrator.
     */
    public function test_the_admin_autocomplete_names_the_developers(): void
    {
        $game = $this->game('Xenon');
        $game->developers()->attach(
            PublisherDeveloper::factory()->create(['pub_dev_name' => 'The Bitmap Brothers']),
            ['developer_role_id' => DB::table('developer_role')->insertGetId(['name' => 'Developer'])]
        );

        $results = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.ajax.games', ['q' => 'Xenon']))
            ->assertOk()
            ->json();

        $this->assertSame('Xenon [The Bitmap Brothers]', $results[0]['game_name']);
        $this->assertSame($game->getKey(), $results[0]['game_id']);
    }

    /**
     * The admin autocomplete ranks by the same three rules, so an editor
     * looking something up sees it in the order the public boxes would.
     */
    public function test_the_admin_autocomplete_ranks_titles_the_same_way(): void
    {
        foreach (['Super Xenon', 'Xenon 2 Megablast', 'Xenon'] as $name) {
            $this->game($name);
        }

        $results = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.ajax.games', ['q' => 'Xenon']))
            ->assertOk()
            ->json();

        $this->assertSame(['Xenon', 'Xenon 2 Megablast', 'Super Xenon'], $this->names($results));
    }

    public function test_the_admin_autocomplete_lists_alphabetically_with_no_query(): void
    {
        $this->game('Zynaps');
        $this->game('Arkanoid');

        $results = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.ajax.games'))
            ->assertOk()
            ->json();

        $this->assertSame(['Arkanoid', 'Zynaps'], $this->names($results));
    }

    public function test_the_admin_autocomplete_is_closed_to_non_admins(): void
    {
        $this->game('Xenon');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.ajax.games', ['q' => 'Xenon']))
            ->assertRedirect('/');
    }

    /**
     * Pin the autocomplete wire format so that a schema rename does not silently
     * break the frontend by changing the keys.
     */
    public function test_the_wire_format_pins_the_primary_keys(): void
    {
        $this->game('Xenon');

        $results = $this->getJson(route('ajax.games', ['q' => 'Xenon']))->assertOk()->json();
        
        $this->assertArrayHasKey('game_id', $results[0], 'The wire format must expose the game_id key');
        $this->assertNotNull($results[0]['game_id'], 'The game_id must not be null');
        $this->assertIsScalar($results[0]['game_id'], 'The game_id must be a scalar');

        $adminResults = $this->actingAs(User::factory()->admin()->create())
            ->getJson(route('admin.ajax.games', ['q' => 'Xenon']))
            ->assertOk()
            ->json();
            
        $this->assertArrayHasKey('game_id', $adminResults[0], 'The wire format must expose the game_id key');
        $this->assertNotNull($adminResults[0]['game_id'], 'The game_id must not be null');
        $this->assertIsScalar($adminResults[0]['game_id'], 'The game_id must be a scalar');
    }
}
