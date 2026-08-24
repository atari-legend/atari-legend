<?php

namespace Tests\Feature\Admin\Games\Releases;

use App\Models\Changelog;
use App\Models\GameRelease;
use App\Models\Trainer;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The scene panel of a release, which is only the list of trainer options a
 * cracked version offers.
 *
 * The list is rewritten wholesale on every save - detached and reattached - so
 * what matters is that the pivot ends up holding exactly what was posted.
 */
class ReleaseSceneTest extends AdminTestCase
{
    public function test_the_scene_panel_loads(): void
    {
        $release = GameRelease::factory()->withTrainer('Infinite lives')->create();

        $this->get(route('admin.games.releases.scene.index', [$release->game, $release]))
            ->assertOk()
            ->assertSee('Infinite lives');
    }

    public function test_trainer_options_are_attached(): void
    {
        $release = GameRelease::factory()->create();
        $lives = Trainer::factory()->create(['name' => 'Infinite lives']);
        $ammo = Trainer::factory()->create(['name' => 'Infinite ammo']);

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]), [
            'trainers' => [$lives->getKey(), $ammo->getKey()],
        ])->assertRedirect(route('admin.games.releases.scene.index', [$release->game, $release]));

        $this->assertEqualsCanonicalizing(
            ['Infinite lives', 'Infinite ammo'],
            $release->fresh()->trainers->pluck('name')->all()
        );

        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->game_name);
    }

    /**
     * Saving the panel with nothing selected is how a trainer is taken off
     * again - there is no per-row delete button.
     */
    public function test_saving_an_empty_list_removes_the_trainers(): void
    {
        $release = GameRelease::factory()->withTrainer('Infinite lives')->create();

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]))
            ->assertRedirect();

        $this->assertCount(0, $release->fresh()->trainers);
    }

    /**
     * Re-saving the same selection must not stack up a second pivot row.
     */
    public function test_saving_the_same_trainer_twice_leaves_one_row(): void
    {
        $release = GameRelease::factory()->create();
        $trainer = Trainer::factory()->create(['name' => 'Infinite lives']);

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]), [
            'trainers' => [$trainer->getKey()],
        ]);

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]), [
            'trainers' => [$trainer->getKey()],
        ]);

        $this->assertCount(1, $release->fresh()->trainers);
    }

    public function test_the_trainers_must_be_posted_as_a_list(): void
    {
        $release = GameRelease::factory()->create();
        $trainer = Trainer::factory()->create();

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]), [
            'trainers' => $trainer->getKey(),
        ])->assertSessionHasErrors('trainers');

        $this->assertCount(0, $release->fresh()->trainers);
        $this->assertNoChangelog();
    }

    public function test_an_unknown_trainer_is_a_404(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.scene.update', [$release->game, $release]), [
            'trainers' => [9999],
        ])->assertNotFound();

        $this->assertCount(0, $release->fresh()->trainers);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $release = GameRelease::factory()->create();

        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.scene.index', [$release->game, $release])
        );
        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.scene.update', [$release->game, $release]),
            'post'
        );
    }
}
