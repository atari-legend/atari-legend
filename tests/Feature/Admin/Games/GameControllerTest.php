<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Control;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\GameRelease;
use App\Models\GameSubmitInfo;
use App\Models\GameVote;
use App\Models\GameVs;
use App\Models\Genre;
use App\Models\Individual;
use App\Models\Language;
use App\Models\MagazineIndex;
use App\Models\MenuDisk;
use App\Models\ProgrammingLanguage;
use App\Models\PublisherDeveloper;
use App\Models\Review;
use App\Models\Screenshot;
use App\Models\Sndh;
use App\Models\SoundHardware;
use Illuminate\Support\Facades\DB;
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

    // Deleting

    /**
     * Create one row of the given relation against the game, so a delete can be
     * tried with exactly one thing hanging off it.
     */
    private function attachDependent(Game $game, string $relation): void
    {
        match ($relation) {
            'releases'    => GameRelease::factory()->create(['game_id' => $game->getKey()]),
            'screenshots' => $game->screenshots()->attach(Screenshot::factory()->create()),
            'facts'       => $game->facts()->create(['game_fact' => 'Written in a fortnight.']),
            'individuals' => $game->individuals()->attach(
                Individual::factory()->create(),
                ['individual_role_id' => DB::table('individual_role')->insertGetId(['name' => 'Coder'])]
            ),
            'developers' => $game->developers()->attach(
                PublisherDeveloper::factory()->create(),
                ['developer_role_id' => DB::table('developer_role')->insertGetId(['name' => 'Developer'])]
            ),
            'sndhs'   => $game->sndhs()->attach(Sndh::factory()->create()),
            'videos'  => $game->videos()->create([
                'title'      => 'Longplay',
                'author'     => 'Someone',
                'youtube_id' => 'dQw4w9WgXcQ',
            ]),
            'reviews'          => $game->reviews()->attach(Review::factory()->create()),
            'menuDiskContents' => $game->menuDiskContents()->create([
                'order'        => 1,
                'menu_disk_id' => MenuDisk::factory()->create()->getKey(),
            ]),
            'magazineIndices' => MagazineIndex::factory()->create(['game_id' => $game->getKey()]),
            'infoSubmissions' => DB::table('game_submitinfo')->insert([
                'game_id'     => $game->getKey(),
                'user_id'     => $this->admin->getKey(),
                'timestamp'   => (string) mktime(12, 0, 0, 6, 1, 2020),
                'submit_text' => 'The musician is Jochen Hippel.',
                'game_done'   => GameSubmitInfo::SUBMISSION_NEW,
            ]),
            'similarGames'        => $game->similarGames()->attach(Game::factory()->create()),
            'similarGamesReverse' => $game->similarGamesReverse()->attach(Game::factory()->create()),
            'akas'                => GameAka::create(['game_id' => $game->getKey(), 'aka_name' => 'Xenon II']),
            'vs'                  => GameVs::create(['atari_id' => $game->getKey(), 'amiga_id' => 1234]),
            'comments'            => $game->comments()->attach(Comment::factory()->create()),
            'votes'               => GameVote::factory()->create([
                'game_id' => $game->getKey(),
                'user_id' => $this->admin->getKey(),
            ]),
        };
    }

    /**
     * A game is deletable only while nothing references it, so what it takes
     * with it is everything the database will not remove on its own: the two
     * tables with no foreign key, and the comment rows behind a pivot that
     * cascades without them.
     */
    public function test_a_deletable_game_takes_its_loose_ends_with_it(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $game->genres()->attach(Genre::factory()->create());
        foreach (['akas', 'vs', 'comments', 'votes'] as $relation) {
            $this->attachDependent($game, $relation);
        }

        $this->delete(route('admin.games.games.destroy', $game))
            ->assertRedirect(route('admin.games.games.index'));

        $this->assertSame(0, Game::query()->count());
        $this->assertSame(0, GameAka::query()->count());
        $this->assertSame(0, GameVs::query()->count());
        $this->assertSame(0, GameVote::query()->count());

        // The pivot cascades, but the comment behind it does not - a comment
        // that belongs to nothing throws when the admin lists it
        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(0, DB::table('game_user_comments')->count());

        // Reference data is an attribute of the game, and goes with it
        $this->assertSame(0, DB::table('game_genre_cross')->count());

        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    /**
     * Spelled out rather than read from the model, so that this is an
     * assertion about what should block rather than an echo of what does.
     */
    public static function blockingRelations(): array
    {
        return [
            ['releases'],
            ['screenshots'],
            ['facts'],
            ['individuals'],
            ['developers'],
            ['sndhs'],
            ['videos'],
            ['reviews'],
            ['menuDiskContents'],
            ['magazineIndices'],
            ['infoSubmissions'],
            ['similarGames'],
            ['similarGamesReverse'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('blockingRelations')]
    public function test_a_game_is_not_deleted_while_something_references_it(string $relation): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $this->attachDependent($game, $relation);

        $this->delete(route('admin.games.games.destroy', $game))
            ->assertRedirect(route('admin.games.games.index'))
            ->assertSessionHas('alert-danger');

        // Not a count: the two `similar games` cases need a second game to be
        // similar to, and it is still there too
        $this->assertNotNull($game->fresh());
        $this->assertNoChangelog();
    }

    public static function nonBlockingRelations(): array
    {
        return [['akas'], ['vs'], ['comments'], ['votes']];
    }

    /**
     * The other half of the pair above: moving a relation between the two lists
     * fails the provider it left, so nothing gets reclassified in silence.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('nonBlockingRelations')]
    public function test_a_game_is_still_deleted_when_only_loose_ends_reference_it(string $relation): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $this->attachDependent($game, $relation);

        $this->delete(route('admin.games.games.destroy', $game))
            ->assertRedirect(route('admin.games.games.index'));

        $this->assertSame(0, Game::query()->count());
    }

    public function test_the_games_table_disables_delete_for_a_game_that_cannot_go(): void
    {
        Game::factory()->named('Xenon')->create();
        Game::factory()->named('Bubble Bobble')->withRelease()->create();

        $response = $this->get(route('admin.games.games.index'))->assertOk();

        // Both rows render a button; the blocked one is named for why it cannot
        // be used, which is also what a screen reader announces.
        //
        // Without escaping: the quotes around the name are literal in the
        // template, so only the name itself comes through escaped.
        $response->assertSee("Delete game 'Xenon'", false);
        $response->assertSee("Cannot delete game 'Bubble Bobble': something still references it", false);
        $response->assertSee('Cannot be deleted: something still references this game', false);
        $response->assertDontSee("Delete game 'Bubble Bobble'", false);
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
