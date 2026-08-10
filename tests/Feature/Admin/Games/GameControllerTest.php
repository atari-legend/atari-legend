<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Control;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\GameVs;
use App\Models\Genre;
use App\Models\Language;
use App\Models\ProgrammingLanguage;
use App\Models\SoundHardware;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Games section: the game itself, its reference-data associations,
 * the multiplayer panel, alternative titles and the Amiga/C64 cross-links.
 *
 * The base-info save is the interesting one. Every association is detached and
 * reattached wholesale, so the tests check that unticking really removes and
 * that saving one panel does not wipe another.
 */
class GameControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Xenon',
            'slug' => 'xenon',
        ], $overrides);
    }

    public function test_index_lists_the_games(): void
    {
        Game::factory()->named('Bubble Bobble')->create();

        $this->get(route('admin.games.games.index'))
            ->assertOk()
            ->assertSee('Bubble Bobble');
    }

    public function test_create_and_edit_forms_load(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        Genre::factory()->create(['name' => 'Shoot-em-up']);

        $this->get(route('admin.games.games.create'))->assertOk()->assertSee('Shoot-em-up');

        $this->get(route('admin.games.games.edit', $game))
            ->assertOk()
            ->assertSee('Xenon');
    }

    public function test_store_creates_the_game_and_stays_on_the_edit_screen(): void
    {
        $this->post(route('admin.games.games.store'), $this->payload())
            ->assertRedirect(route('admin.games.games.edit', Game::sole()));

        $game = Game::sole();

        $this->assertSame('Xenon', $game->game_name);
        $this->assertSame('xenon', $game->slug);
        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');
    }

    public function test_store_requires_a_name(): void
    {
        $this->post(route('admin.games.games.store'), ['slug' => 'xenon'])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Game::query()->count());
        $this->assertNoChangelog();
    }

    /**
     * The slug is what the public page binds on, so it has to stay unique and
     * URL-safe.
     */
    public function test_the_slug_must_be_unique_and_well_formed(): void
    {
        Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.games.store'), $this->payload(['name' => 'Xenon 2']))
            ->assertSessionHasErrors('slug');

        $this->post(route('admin.games.games.store'), $this->payload(['slug' => 'Not A Slug!']))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Game::query()->count());
    }

    /**
     * A game keeps its own slug when it is saved without renaming.
     */
    public function test_a_game_may_keep_its_own_slug(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->put(route('admin.games.games.update', $game), $this->payload(['name' => 'Xenon renamed']))
            ->assertRedirect(route('admin.games.games.edit', $game));

        $this->assertSame('Xenon renamed', $game->fresh()->game_name);
        $this->assertSame('xenon', $game->fresh()->slug);
    }

    public function test_base_info_attaches_the_reference_data(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $genre = Genre::factory()->create(['name' => 'Shoot-em-up']);
        $engine = Engine::forceCreate(['name' => 'STOS']);
        $control = Control::forceCreate(['name' => 'Joystick']);
        $sound = SoundHardware::forceCreate(['name' => 'YM2149']);
        $language = ProgrammingLanguage::forceCreate(['name' => 'Assembly']);

        $this->post(route('admin.games.games.update.base-info', $game), $this->payload([
            'genres'    => [$genre->getKey()],
            'engines'   => [$engine->getKey()],
            'controls'  => [$control->getKey()],
            'sound'     => [$sound->getKey()],
            'languages' => [$language->getKey()],
        ]))->assertRedirect(route('admin.games.games.edit', $game));

        $game->refresh();

        $this->assertSame(['Shoot-em-up'], $game->genres->pluck('name')->all());
        $this->assertSame(['STOS'], $game->engines->pluck('name')->all());
        $this->assertSame(['Joystick'], $game->controls->pluck('name')->all());
        $this->assertSame(['YM2149'], $game->soundHardwares->pluck('name')->all());
        $this->assertSame(['Assembly'], $game->programmingLanguages->pluck('name')->all());
    }

    /**
     * Associations are detached and reattached on every save, so posting
     * without a genre has to clear the ones that were there.
     */
    public function test_unticking_a_genre_removes_it(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $game->genres()->attach(Genre::factory()->create());

        $this->post(route('admin.games.games.update.base-info', $game), $this->payload());

        $this->assertCount(0, $game->fresh()->genres);
    }

    public function test_multiplayer_details_are_saved(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.games.update.multiplayer', $game), [
            'players'              => 2,
            'players_linked'       => 4,
            'multiplayer_type'     => 'Simultaneous',
            'multiplayer_hardware' => 'Midi-Link',
        ])->assertRedirect(route('admin.games.games.edit', $game));

        $game->refresh();

        $this->assertSame(2, $game->number_players_on_same_machine);
        $this->assertSame(4, $game->number_players_multiple_machines);
        $this->assertSame('Simultaneous', $game->multiplayer_type);
        $this->assertSame('Midi-Link', $game->multiplayer_hardware);
        $this->assertChangelog(Changelog::UPDATE, 'Games', 'Xenon');
    }

    /**
     * The type and hardware are closed lists, not free text.
     */
    public function test_multiplayer_rejects_values_outside_the_lists(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.games.update.multiplayer', $game), [
            'multiplayer_type'     => 'Whenever',
            'multiplayer_hardware' => 'Carrier pigeon',
            'players'              => 'lots',
        ])->assertSessionHasErrors(['multiplayer_type', 'multiplayer_hardware', 'players']);
    }

    public function test_an_alternative_title_can_be_added_and_removed(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();
        $language = Language::factory()->create(['id' => 'ja', 'name' => 'Japanese']);

        $this->post(route('admin.games.games.aka.store', $game), [
            'aka'      => 'Baburu Boburu',
            'language' => $language->id,
        ])->assertRedirect(route('admin.games.games.edit', $game));

        $aka = GameAka::sole();

        $this->assertSame('Baburu Boburu', $aka->aka_name);
        $this->assertSame('ja', $aka->language_id);
        $this->assertChangelog(Changelog::INSERT, 'Games', 'Bubble Bobble');

        $this->delete(route('admin.games.games.destroy.aka', [$game, $aka]))
            ->assertRedirect(route('admin.games.games.edit', $game));

        $this->assertSame(0, GameAka::query()->count());
    }

    public function test_a_cross_link_can_be_added_and_removed(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.games.vs.store', $game), [
            'amiga_id'     => 1234,
            'lemon64_slug' => 'xenon',
        ])->assertRedirect(route('admin.games.games.edit', $game));

        $vs = GameVs::sole();

        $this->assertSame(1234, $vs->amiga_id);
        $this->assertSame('xenon', $vs->lemon64_slug);
        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');

        $this->delete(route('admin.games.games.destroy.vs', [$game, 1234]))
            ->assertRedirect(route('admin.games.games.edit', $game));

        $this->assertSame(0, GameVs::query()->count());
    }

    public function test_removing_an_unknown_cross_link_is_a_404(): void
    {
        $game = Game::factory()->create();

        $this->delete(route('admin.games.games.destroy.vs', [$game, 9999]))->assertNotFound();
    }

    /**
     * Deleting a game is deliberately not implemented - a game has releases,
     * dumps and scans hanging off it. The button must not quietly do nothing.
     */
    public function test_deleting_a_game_is_not_implemented(): void
    {
        $game = Game::factory()->create();

        $this->withoutExceptionHandling();
        $this->expectException(\Error::class);

        $this->delete(route('admin.games.games.destroy', $game));
    }

    public function test_non_admins_are_turned_away(): void
    {
        $game = Game::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.games.games.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.games.edit', $game));
        $this->assertNonAdminIsTurnedAway(route('admin.games.games.store'), 'post', $this->payload());

        $this->assertSame(1, Game::query()->count());
    }
}
