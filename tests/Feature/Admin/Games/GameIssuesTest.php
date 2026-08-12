<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Release;
use App\Models\ReleaseScan;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The data-quality report: one page listing the games and releases that are
 * missing something the site needs.
 *
 * Each card is a query looking for one defect, so every test here pairs a game
 * that has the defect with one that does not - a card that listed everything,
 * or nothing, would be just as useless and both look fine from a status code.
 */
class GameIssuesTest extends AdminTestCase
{
    /**
     * A game with none of the defects the report looks for: it has a release,
     * a screenshot, a genre and a hand-written slug, so it must not show up in
     * any of the cards.
     */
    private function cleanGame(string $name, ?Genre $genre = null): Game
    {
        $game = Game::factory()->named($name)->withScreenshot()->create();
        $game->genres()->attach($genre ?? Genre::factory()->create());

        ReleaseScan::factory()->create([
            'game_release_id' => Release::factory()->create(['game_id' => $game->getKey()]),
        ]);

        return $game;
    }

    public function test_the_report_renders(): void
    {
        $this->get(route('admin.games.issues'))
            ->assertOk()
            ->assertSee('without a release')
            ->assertSee('without screenshots')
            ->assertSee('with a bad URL slug')
            ->assertSee('without box scans');
    }

    public function test_only_a_game_without_a_release_is_listed(): void
    {
        $this->cleanGame('Xenon');
        Game::factory()->named('Lonely Game')->withScreenshot()->create();

        $this->get(route('admin.games.issues'))
            ->assertSee('1 game without a release')
            ->assertSee('Lonely Game')
            ->assertDontSee('Xenon');
    }

    public function test_only_a_game_without_a_screenshot_is_listed(): void
    {
        $this->cleanGame('Xenon');
        Game::factory()->named('Blank Game')->withRelease()->create();

        $this->get(route('admin.games.issues'))
            ->assertSee('1 game without screenshots')
            ->assertSee('Blank Game')
            ->assertDontSee('Xenon');
    }

    /**
     * The legacy site slugged games as "name-id-123"; those are the ones that
     * still need a hand-written slug, and are matched on the "-id-" infix.
     */
    public function test_only_a_game_with_a_legacy_slug_is_listed(): void
    {
        $genre = Genre::factory()->create();

        $this->cleanGame('Xenon', $genre);
        $this->cleanGame('Legacy Game', $genre)->update(['slug' => 'legacy-game-id-42']);

        $this->get(route('admin.games.issues'))
            ->assertSee('with a bad URL slug')
            ->assertSee('legacy-game-id-42')
            ->assertSee('Legacy Game')
            ->assertDontSee('Xenon');
    }

    /**
     * Only commercial releases are expected to have come in a box, so a
     * non-commercial release without scans is not an issue.
     */
    public function test_only_a_commercial_release_without_scans_is_listed(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $scanned = Release::factory()->create([
            'game_id' => $game->getKey(),
            'name'    => 'Boxed',
            'license' => Release::LICENCE_COMMERCIAL,
        ]);
        ReleaseScan::factory()->create(['game_release_id' => $scanned->getKey()]);

        Release::factory()->create([
            'game_id' => $game->getKey(),
            'name'    => 'Unboxed',
            'license' => Release::LICENCE_COMMERCIAL,
        ]);

        Release::factory()->create([
            'game_id' => $game->getKey(),
            'name'    => 'Freeware',
            'license' => Release::LICENSE_NON_COMMERCIAL,
        ]);

        $this->get(route('admin.games.issues'))
            ->assertSee('without box scans')
            ->assertSee('as Unboxed')
            ->assertDontSee('as Boxed')
            ->assertDontSee('as Freeware');
    }

    /**
     * The genre card offers one game at a time - it only has something to show
     * when a game has screenshots (so somebody can tell what it is) but no
     * genre yet.
     */
    public function test_the_genre_card_offers_a_game_that_has_screenshots_but_no_genre(): void
    {
        $genre = Genre::factory()->create(['name' => 'Shoot-em-up']);

        Game::factory()->named('Xenon')->withScreenshot()->create()->genres()->attach($genre);

        $this->get(route('admin.games.issues'))->assertDontSee('genres are…', false);

        Game::factory()->named('Unsorted Game')->withScreenshot()->create();

        $this->get(route('admin.games.issues'))
            ->assertSee('Unsorted Game')
            ->assertSee('genres are…', false);
    }

    public function test_genres_can_be_set_from_the_report(): void
    {
        $game = Game::factory()->named('Xenon')->withScreenshot()->create();

        $shooter = Genre::factory()->create(['name' => 'Shoot-em-up']);
        $platform = Genre::factory()->create(['name' => 'Platform']);

        $this->post(route('admin.games.issues.genres', $game), [
            'genres' => [$shooter->getKey(), $platform->getKey()],
        ])->assertRedirect(route('admin.games.issues'));

        $this->assertEqualsCanonicalizing(
            ['Shoot-em-up', 'Platform'],
            $game->fresh()->genres->pluck('name')->all()
        );

        $this->assertChangelog(Changelog::UPDATE, 'Games', 'Xenon');
    }

    public function test_the_report_is_closed_to_non_admins(): void
    {
        $this->assertNonAdminIsTurnedAway(route('admin.games.issues'));
    }
}
