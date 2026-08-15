<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Game;
use App\Models\Sndh;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The SNDH tunes attached to a game.
 *
 * Two controllers share the job: `GameMusicController` works on one game at a
 * time, from that game's music panel, while `MusicController` is the batch
 * screen that walks the games with no music at all and proposes matches for
 * several of them at once. The pairs they write are the same rows in
 * `game_sndh`, so what is tested here is which of them each action touches.
 *
 * Neither index page can be exercised here: both look for candidate tunes with
 * `MATCH(title) AGAINST(?)`, which the SQLite test database has no equivalent
 * for. Only the write paths are covered.
 *
 * The detach route carries the tune's key in the URL. Keys do not carry a
 * `.sndh` extension; the template appends it when generating links.
 */
class GameMusicTest extends AdminTestCase
{
    // One game at a time

    public function test_a_song_can_be_attached_to_a_game(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $sndh = Sndh::factory()->create(['title' => 'Xenon']);

        $this->post(route('admin.games.game-music.store', $game), ['sndh' => $sndh->getKey()])
            ->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertSame([$sndh->getKey()], $game->fresh()->sndhs->modelKeys());

        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');
        $this->assertSame($sndh->getKey(), Changelog::sole()->sub_section_name);
    }

    /**
     * The panel is reachable from a game that already has the tune, so a second
     * submission must not produce a duplicate row - the game page lists one
     * entry per pivot row, subtunes included.
     */
    public function test_a_song_is_only_attached_once(): void
    {
        $game = Game::factory()->create();
        $sndh = Sndh::factory()->create();

        $this->post(route('admin.games.game-music.store', $game), ['sndh' => $sndh->getKey()]);
        $this->post(route('admin.games.game-music.store', $game), ['sndh' => $sndh->getKey()]);

        $this->assertCount(1, $game->fresh()->sndhs);
        $this->assertSame(1, Changelog::query()->count());
    }

    public function test_an_unknown_song_is_ignored(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-music.store', $game), ['sndh' => 'Musicians/Nobody/none.sndh'])
            ->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertCount(0, $game->fresh()->sndhs);
        $this->assertNoChangelog();
    }

    /**
     * The key of a tune is its path inside the archive, slashes and all, so the
     * route has to carry it as several URL segments. The key used here has no
     * extension: the route constraint does not allow a dot, see the note in the
     * class docblock.
     */
    public function test_a_song_can_be_detached_from_a_game(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $sndh = Sndh::factory()->create(['id' => 'Musicians/Hippel/Xenon']);
        $other = Sndh::factory()->create();

        $game->sndhs()->attach([$sndh->getKey(), $other->getKey()]);

        $this->delete(route('admin.games.game-music.destroy', ['game' => $game, 'sndh' => $sndh]))
            ->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertSame([$other->getKey()], $game->fresh()->sndhs->modelKeys());

        $this->assertChangelog(Changelog::DELETE, 'Games', 'Xenon');
    }

    public function test_a_song_with_unusual_characters_can_be_detached(): void
    {
        $game = Game::factory()->named('Pareidolia')->create();
        $sndh = Sndh::factory()->create(['id' => 'AD/Pareidolia+MadMax_megamix']);

        $game->sndhs()->attach($sndh->getKey());

        $this->delete(route('admin.games.game-music.destroy', ['game' => $game, 'sndh' => $sndh]))
            ->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertCount(0, $game->fresh()->sndhs);
        $this->assertChangelog(Changelog::DELETE, 'Games', 'Pareidolia');
    }

    /**
     * The candidates card ticks off several proposals at once; each pair gets
     * its own changelog entry.
     */
    public function test_several_candidates_can_be_associated_at_once(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $first = Sndh::factory()->create(['title' => 'Xenon title']);
        $second = Sndh::factory()->create(['title' => 'Xenon ingame']);
        Sndh::factory()->create();

        $this->post(route('admin.games.game-music.associate', $game), [
            'associations' => [$first->getKey(), $second->getKey()],
        ])->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertEqualsCanonicalizing(
            [$first->getKey(), $second->getKey()],
            $game->fresh()->sndhs->modelKeys()
        );

        $this->assertSame(2, Changelog::query()->where('action', Changelog::INSERT)->count());
        $this->assertSame(2, DB::table('game_sndh')->count());
    }

    public function test_associating_nothing_changes_nothing(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.game-music.associate', $game), [])
            ->assertRedirect(route('admin.games.game-music.index', $game));

        $this->assertCount(0, $game->fresh()->sndhs);
        $this->assertNoChangelog();
    }

    // Across games

    /**
     * The batch screen takes 'game:tune' pairs, so one submission can associate
     * tunes with several different games.
     */
    public function test_the_batch_screen_associates_pairs_of_game_and_song(): void
    {
        $xenon = Game::factory()->named('Xenon')->create();
        $turrican = Game::factory()->named('Turrican')->create();

        $xenonTune = Sndh::factory()->create(['title' => 'Xenon']);
        $turricanTune = Sndh::factory()->create(['title' => 'Turrican']);

        $this->post(route('admin.games.music.associate'), [
            'associations' => [
                $xenon->getKey() . ':' . $xenonTune->getKey(),
                $turrican->getKey() . ':' . $turricanTune->getKey(),
            ],
        ])
            ->assertRedirect(route('admin.games.music'))
            ->assertSessionHas('alert-success');

        $this->assertSame([$xenonTune->getKey()], $xenon->fresh()->sndhs->modelKeys());
        $this->assertSame([$turricanTune->getKey()], $turrican->fresh()->sndhs->modelKeys());

        $this->assertChangelog(Changelog::INSERT, 'Games', 'Xenon');
        $this->assertChangelog(Changelog::INSERT, 'Games', 'Turrican');
    }

    public function test_the_batch_screen_associates_nothing_when_nothing_is_ticked(): void
    {
        Game::factory()->create();

        $this->post(route('admin.games.music.associate'), [])
            ->assertRedirect(route('admin.games.music'))
            ->assertSessionHas('alert-success');

        $this->assertSame(0, Changelog::query()->count());
    }

    public function test_the_music_screens_are_closed_to_non_admins(): void
    {
        $game = Game::factory()->create();

        $this->assertNonAdminIsTurnedAway(route('admin.games.music'));
        $this->assertNonAdminIsTurnedAway(route('admin.games.game-music.index', $game));
    }
}
