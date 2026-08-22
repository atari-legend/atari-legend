<?php

namespace Tests\Feature\Admin\Games;

use App\Livewire\Admin\Games\GameSeriesTable;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameSeries;
use Livewire\Livewire;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Game series section: the series itself and the games attached to it.
 *
 * The delete is the interesting one. game.game_series_id is ON DELETE RESTRICT,
 * but Laravel cannot ALTER TABLE a foreign key onto SQLite, so the constraint is
 * absent here and the refusal has to be asserted rather than assumed.
 */
class GameSeriesControllerTest extends AdminTestCase
{
    public function test_index_lists_the_series(): void
    {
        GameSeries::forceCreate(['name' => 'Xenon series']);

        $this->get(route('admin.games.series.index'))
            ->assertOk()
            ->assertSee('Xenon series');
    }

    public function test_create_and_edit_forms_load(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);
        Game::factory()->named('Xenon 2 Megablast')->create(['game_series_id' => $series->id]);

        $this->get(route('admin.games.series.create'))->assertOk();

        $this->get(route('admin.games.series.edit', $series))
            ->assertOk()
            ->assertSee('Xenon series')
            ->assertSee('Xenon 2 Megablast');
    }

    public function test_store_creates_the_series_and_opens_it(): void
    {
        $this->post(route('admin.games.series.store'), ['name' => 'Xenon series'])
            ->assertRedirect(route('admin.games.series.edit', GameSeries::sole()));

        $this->assertSame('Xenon series', GameSeries::sole()->name);
        $this->assertChangelog(Changelog::INSERT, 'Game series', 'Xenon series');
    }

    /**
     * The changelog records the name the series had *before* the edit, so that
     * the entry still identifies what was renamed.
     */
    public function test_update_renames_the_series(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon']);

        $this->put(route('admin.games.series.update', $series), ['name' => 'Xenon series'])
            ->assertRedirect(route('admin.games.series.index'));

        $this->assertSame('Xenon series', $series->fresh()->name);
        $this->assertChangelog(Changelog::UPDATE, 'Game series', 'Xenon');
    }

    public function test_a_game_can_be_added_to_a_series(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.series.game.store', $series), ['game' => $game->getKey()])
            ->assertRedirect(route('admin.games.series.edit', $series));

        $this->assertSame($series->id, $game->fresh()->game_series_id);
        $this->assertChangelog(Changelog::INSERT, 'Game series', 'Xenon series');
    }

    public function test_removing_a_game_detaches_it_without_deleting_it(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);
        $game = Game::factory()->named('Xenon')->create(['game_series_id' => $series->id]);

        $this->delete(route('admin.games.series.game.destroy', ['series' => $series, 'game' => $game]))
            ->assertRedirect(route('admin.games.series.edit', $series));

        $this->assertNull($game->fresh()->game_series_id);
        $this->assertDatabaseHas('game', ['id' => $game->getKey()]);
        $this->assertChangelog(Changelog::DELETE, 'Game series', 'Xenon series');
    }

    public function test_destroy_deletes_a_series_that_has_no_games(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);

        $this->delete(route('admin.games.series.destroy', $series))
            ->assertRedirect(route('admin.games.series.index'))
            ->assertSessionMissing('alert-danger');

        $this->assertSame(0, GameSeries::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Game series', 'Xenon series');
    }

    public function test_destroy_refuses_a_series_that_still_has_games(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);
        $game = Game::factory()->named('Xenon')->create(['game_series_id' => $series->id]);

        $this->delete(route('admin.games.series.destroy', $series))
            ->assertRedirect(route('admin.games.series.index'))
            ->assertSessionHas('alert-danger');

        $this->assertDatabaseHas('game_series', ['id' => $series->id]);
        // Refusing must not be a half-delete: the game keeps its series.
        $this->assertSame($series->id, $game->fresh()->game_series_id);
        $this->assertNoChangelog();
    }

    /**
     * Matched on the button titles, not the form actions: the name column links
     * to '<series>/edit', which contains the destroy URL as a prefix, so a URL
     * substring check could never say the form was absent. The quotes are
     * literal template text rather than escaped output, hence $escape = false.
     */
    public function test_the_delete_button_is_disabled_while_games_remain(): void
    {
        $empty = GameSeries::forceCreate(['name' => 'Empty series']);
        $full = GameSeries::forceCreate(['name' => 'Full series']);
        Game::factory()->named('Xenon')->create(['game_series_id' => $full->id]);

        Livewire::test(GameSeriesTable::class)
            ->assertSee("Delete series 'Empty series'", false)
            ->assertDontSee("Delete series 'Full series'", false)
            ->assertSee("'Full series' still has 1 game(s). Remove them first.", false);
    }

    public function test_a_non_administrator_is_turned_away(): void
    {
        $series = GameSeries::forceCreate(['name' => 'Xenon series']);

        $this->assertNonAdminIsTurnedAway(route('admin.games.series.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.series.destroy', $series), 'delete');

        $this->assertDatabaseHas('game_series', ['id' => $series->id]);
    }
}
