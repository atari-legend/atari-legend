<?php

namespace Tests\Feature\Helpers;

use App\Helpers\ReleaseHelper;
use App\Models\Game;
use App\Models\Release;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The breadcrumb trail that lets an editor step between the other releases of
 * the same game.
 */
class ReleaseHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lone_release_has_no_siblings(): void
    {
        $this->assertSame([], ReleaseHelper::siblingReleasesCrumbs(Release::factory()->create()));
    }

    /**
     * @return string[] Crumb labels, in the order the helper returned them
     */
    private function labels(array $crumbs): array
    {
        return array_values(array_map(fn ($crumb) => $crumb->label, $crumbs));
    }

    public function test_siblings_are_listed_oldest_first_and_exclude_the_release_itself(): void
    {
        $game = Game::factory()->create();

        Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1988-01-01', 'name' => 'Original']);
        $second = Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1990-01-01', 'name' => 'Budget']);
        Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1992-01-01', 'name' => 'Compilation']);

        $crumbs = ReleaseHelper::siblingReleasesCrumbs($second->fresh());

        $this->assertSame(['1988 as Original', '1992 as Compilation'], $this->labels($crumbs));
    }

    /**
     * Callers only ever iterate the crumbs, so this pins the count and content
     * rather than how the array is keyed.
     */
    public function test_only_the_current_release_is_left_out(): void
    {
        $game = Game::factory()->create();

        Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1988-01-01', 'name' => 'Original']);
        $current = Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1990-01-01', 'name' => 'Budget']);

        $labels = $this->labels(ReleaseHelper::siblingReleasesCrumbs($current->fresh()));

        $this->assertSame(['1988 as Original'], $labels);
        $this->assertNotContains('1990 as Budget', $labels);
    }

    public function test_crumbs_link_to_the_route_they_were_given(): void
    {
        $game = Game::factory()->create();
        Release::factory()->create(['game_id' => $game->getKey()]);
        $release = Release::factory()->create(['game_id' => $game->getKey()]);

        $crumbs = ReleaseHelper::siblingReleasesCrumbs($release->fresh(), 'games.releases.show');

        $this->assertStringContainsString('/games/release/', reset($crumbs)->route);
    }

    /**
     * A release with no date sorts under '[no date]' rather than crashing on a
     * null year.
     */
    public function test_undated_siblings_are_still_listed(): void
    {
        $game = Game::factory()->create();

        Release::factory()->undated()->create(['game_id' => $game->getKey(), 'name' => 'Unknown']);
        $release = Release::factory()->create(['game_id' => $game->getKey(), 'date' => '1988-01-01']);

        $crumbs = ReleaseHelper::siblingReleasesCrumbs($release->fresh());

        $this->assertCount(1, $crumbs);
        $this->assertSame('[no date] as Unknown', reset($crumbs)->label);
    }

    /**
     * The label gathers everything that tells two releases of one game apart.
     */
    public function test_the_label_names_publisher_and_locations(): void
    {
        $game = Game::factory()->create();
        Release::factory()->create(['game_id' => $game->getKey()]);

        $sibling = Release::factory()
            ->publishedBy('Ocean')
            ->releasedIn('France')
            ->create(['game_id' => $game->getKey(), 'date' => '1988-01-01', 'name' => 'Original']);

        $release = Release::factory()->create(['game_id' => $game->getKey()]);

        $crumbs = ReleaseHelper::siblingReleasesCrumbs($release->fresh());

        $this->assertContains(
            '1988 as Original by Ocean in France',
            $this->labels($crumbs)
        );
        $this->assertNotNull($sibling->fresh());
    }

    public function test_releases_of_another_game_are_not_siblings(): void
    {
        $release = Release::factory()->create();
        Release::factory()->count(3)->create();

        $this->assertSame([], ReleaseHelper::siblingReleasesCrumbs($release->fresh()));
    }
}
