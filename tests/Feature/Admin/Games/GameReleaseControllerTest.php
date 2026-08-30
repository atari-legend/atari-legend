<?php

namespace Tests\Feature\Admin\Games;

use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Language;
use App\Models\Location;
use App\Models\PubDev;
use App\Models\GameReleaseAka;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin release editor.
 *
 * A release carries a year rather than a full date, a publisher, and several
 * lists - locations, crews, languages, distributors - each detached and
 * reattached on save.
 */
class GameReleaseControllerTest extends AdminTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'    => 'Original',
            'year'    => 1988,
            'type'    => null,
            'license' => GameRelease::LICENCE_COMMERCIAL,
            'status'  => null,
            'notes'   => null,
        ], $overrides);
    }

    public function test_the_release_list_and_forms_load(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $release = GameRelease::factory()->publishedBy('Bitmap Brothers')->create(['game_id' => $game->getKey()]);

        $this->get(route('admin.games.releases.index', $game))->assertOk()->assertSee('Xenon');
        $this->get(route('admin.games.releases.create', $game))->assertOk();
        $this->get(route('admin.games.releases.show', [$game, $release]))->assertOk()->assertSee('Bitmap Brothers');
    }

    public function test_store_creates_the_release_against_its_game(): void
    {
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.games.releases.store', $game), $this->payload())
            ->assertRedirect(route('admin.games.releases.show', [$game, GameRelease::sole()]));

        $release = GameRelease::sole();

        $this->assertSame($game->getKey(), $release->game_id);
        $this->assertSame('Original', $release->name);
        $this->assertSame(GameRelease::LICENCE_COMMERCIAL, $release->license);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', 'Xenon');
    }

    /**
     * Only a year is captured; it is stored as the first of January.
     */
    public function test_the_year_becomes_the_first_of_january(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.releases.store', $game), $this->payload(['year' => 1992]));

        $this->assertSame('1992-01-01', GameRelease::sole()->date->format('Y-m-d'));
    }

    public function test_a_release_can_have_no_year(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.releases.store', $game), $this->payload(['year' => null]));

        $this->assertNull(GameRelease::sole()->date);
        $this->assertSame('[no date]', GameRelease::sole()->year);
    }

    /**
     * The Atari ST predates nothing before 1984, and a release cannot be in the
     * future.
     */
    public function test_the_year_must_be_within_the_platforms_lifetime(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.releases.store', $game), $this->payload(['year' => 1979]))
            ->assertSessionHasErrors('year');

        $this->post(route('admin.games.releases.store', $game), $this->payload([
            'year' => (int) date('Y') + 1,
        ]))->assertSessionHasErrors('year');

        $this->assertSame(0, GameRelease::query()->count());
    }

    public function test_type_license_and_status_come_from_closed_lists(): void
    {
        $game = Game::factory()->create();

        $this->post(route('admin.games.releases.store', $game), $this->payload([
            'type'    => 'Bootleg',
            'license' => 'Shareware',
            'status'  => 'Rumoured',
        ]))->assertSessionHasErrors(['type', 'license', 'status']);

        $this->assertSame(0, GameRelease::query()->count());
    }

    public function test_a_publisher_can_be_set(): void
    {
        $game = Game::factory()->create();
        $publisher = PubDev::factory()->create(['pub_dev_name' => 'Ocean']);

        $this->post(route('admin.games.releases.store', $game), $this->payload([
            'publisher' => $publisher->getKey(),
        ]))->assertRedirect();

        $this->assertSame('Ocean', GameRelease::sole()->publisher->pub_dev_name);
    }

    public function test_locations_crews_languages_and_distributors_are_attached(): void
    {
        $game = Game::factory()->create();
        $location = Location::factory()->create(['name' => 'France']);
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);
        $language = Language::factory()->create(['id' => 'fr', 'name' => 'French']);
        $distributor = PubDev::factory()->create(['pub_dev_name' => 'Erbe']);

        $this->post(route('admin.games.releases.store', $game), $this->payload([
            'locations'    => [$location->getKey()],
            'crews'        => [$crew->getKey()],
            'languages'    => [$language->id],
            'distributors' => [$distributor->getKey()],
        ]))->assertRedirect();

        $release = GameRelease::sole();

        $this->assertSame(['France'], $release->locations->pluck('name')->all());
        $this->assertSame(['The Replicants'], $release->crews->pluck('crew_name')->all());
        $this->assertSame(['fr'], $release->languages->pluck('id')->all());
        $this->assertSame([$distributor->getKey()], $release->distributors->pluck('id')->all());
    }

    public function test_update_rewrites_the_release(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);

        $this->put(route('admin.games.releases.update', [$game, $release]), $this->payload([
            'name'  => 'Budget re-release',
            'type'  => 'Budget',
            'notes' => 'Cassette only.',
        ]))->assertRedirect(route('admin.games.releases.index', $game));

        $release->refresh();

        $this->assertSame('Budget re-release', $release->name);
        $this->assertSame('Budget', $release->type);
        $this->assertSame('Cassette only.', $release->notes);
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', 'Xenon');
    }

    /**
     * Clearing the publisher used to abort the save with a 404, because the
     * empty field reached findOrFail(null) - so a release could never have its
     * publisher taken off again.
     */
    public function test_the_publisher_can_be_removed(): void
    {
        $release = GameRelease::factory()->publishedBy('Ocean')->create();

        $this->put(
            route('admin.games.releases.update', [$release->game, $release]),
            $this->payload(['publisher' => null])
        )->assertRedirect(route('admin.games.releases.index', $release->game));

        $this->assertNull($release->fresh()->pub_dev_id);
    }

    public function test_the_publisher_can_be_swapped(): void
    {
        $release = GameRelease::factory()->publishedBy('Ocean')->create();
        $other = PubDev::factory()->create(['pub_dev_name' => 'US Gold']);

        $this->put(
            route('admin.games.releases.update', [$release->game, $release]),
            $this->payload(['publisher' => $other->getKey()])
        )->assertRedirect();

        $this->assertSame('US Gold', $release->fresh()->publisher->pub_dev_name);
    }

    public function test_an_unknown_publisher_is_a_404(): void
    {
        $release = GameRelease::factory()->create();

        $this->put(
            route('admin.games.releases.update', [$release->game, $release]),
            $this->payload(['publisher' => 9999])
        )->assertNotFound();
    }

    /**
     * Clearing a list on save has to remove what was there.
     */
    public function test_clearing_the_crews_removes_them(): void
    {
        $release = GameRelease::factory()->crackedBy('The Replicants')->create();

        $this->put(
            route('admin.games.releases.update', [$release->game, $release]),
            $this->payload()
        )->assertRedirect();

        $this->assertCount(0, $release->fresh()->crews);
    }

    public function test_destroy_removes_the_release(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);

        $this->delete(route('admin.games.releases.destroy', [$game, $release]))
            ->assertRedirect(route('admin.games.releases.index', $game));

        $this->assertSame(0, GameRelease::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Game Release', 'Xenon');
    }

    public function test_an_alternative_title_can_be_added_and_removed(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);
        $language = Language::factory()->create(['id' => 'ja', 'name' => 'Japanese']);

        $this->post(route('admin.games.releases.aka.store', [$game, $release]), [
            'aka'      => 'Baburu Boburu',
            'language' => $language->id,
        ])->assertRedirect(route('admin.games.releases.show', [$game, $release]));

        $aka = GameReleaseAka::sole();

        $this->assertSame('Baburu Boburu', $aka->name);
        $this->assertSame('ja', $aka->language_id);

        $this->delete(route('admin.games.releases.aka.destroy', [$game, $release, $aka]))
            ->assertRedirect(route('admin.games.releases.show', [$game, $release]));

        $this->assertSame(0, GameReleaseAka::query()->count());
    }

    public function test_non_admins_are_turned_away(): void
    {
        $game = Game::factory()->create();
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);

        $this->assertNonAdminIsTurnedAway(route('admin.games.releases.index', $game));
        $this->assertNonAdminIsTurnedAway(route('admin.games.releases.show', [$game, $release]));
    }
}
