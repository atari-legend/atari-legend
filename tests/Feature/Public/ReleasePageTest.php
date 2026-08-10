<?php

namespace Tests\Feature\Public;

use App\Models\Game;
use App\Models\GameVote;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\Release;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The release page, the vote endpoint and the image routes.
 */
class ReleasePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_release_page_describes_the_release(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $release = Release::factory()
            ->publishedBy('Ocean')
            ->create(['game_id' => $game->getKey(), 'date' => '1988-06-01']);

        $response = $this->get(route('games.releases.show', $release))->assertOk()->assertSee('Xenon');

        $this->assertNotEmpty($response->viewData('descriptions'));
    }

    /**
     * A release that only exists as an entry on a menu disk has no page of its
     * own - it is reached through the menu.
     */
    public function test_a_release_that_only_exists_on_a_menu_has_no_page(): void
    {
        $release = Release::factory()->create();

        $set = MenuSet::factory()->create();
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey()]);
        $disk = MenuDisk::factory()->create(['menu_id' => $menu->getKey()]);

        MenuDiskContent::forceCreate([
            'menu_disk_id'    => $disk->getKey(),
            'order'           => 1,
            'game_release_id' => $release->getKey(),
        ]);

        $this->get(route('games.releases.show', $release))->assertNotFound();
    }

    public function test_only_box_scans_are_shown_as_box_scans(): void
    {
        $release = Release::factory()->create();

        foreach (['Box front', 'Poster'] as $type) {
            DB::table('game_release_scan')->insert([
                'game_release_id' => $release->getKey(),
                'type'            => $type,
                'imgext'          => 'jpg',
            ]);
        }

        $this->assertCount(
            1,
            $this->get(route('games.releases.show', $release))->assertOk()->viewData('boxscans')
        );
    }

    public function test_a_release_page_carries_structured_data(): void
    {
        $game = Game::factory()->named('Xenon')->create();
        $release = Release::factory()->create(['game_id' => $game->getKey()]);

        $jsonLd = $this->get(route('games.releases.show', $release))
            ->assertOk()
            ->viewData('jsonLd')
            ->json();

        $this->assertStringContainsString('"@type": "VideoGame"', $jsonLd);
        $this->assertStringContainsString('Xenon', $jsonLd);
    }

    // Voting

    public function test_a_signed_in_visitor_can_vote(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('games.vote', $game), ['score' => 4])
            ->assertRedirect(route('games.show', $game));

        $vote = GameVote::sole();

        $this->assertSame(4, $vote->score);
        $this->assertSame($user->user_id, $vote->user_id);
    }

    public function test_voting_again_replaces_the_previous_vote(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.vote', $game), ['score' => 4]);
        $this->actingAs($user)->post(route('games.vote', $game), ['score' => 1]);

        $this->assertSame(1, GameVote::query()->count());
        $this->assertSame(1, GameVote::sole()->score);
    }

    public function test_a_vote_can_be_withdrawn(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('games.vote', $game), ['score' => 4]);

        $this->actingAs($user)
            ->post(route('games.vote', $game), ['remove' => 'remove'])
            ->assertRedirect(route('games.show', $game));

        $this->assertSame(0, GameVote::query()->count());
    }

    public function test_a_score_outside_the_scale_is_rejected(): void
    {
        $game = Game::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('games.vote', $game), ['score' => 9])
            ->assertSessionHasErrors('score');

        $this->assertSame(0, GameVote::query()->count());
    }

    public function test_a_guest_cannot_vote(): void
    {
        $game = Game::factory()->create();

        $this->post(route('games.vote', $game), ['score' => 4])
            ->assertRedirect(route('login'));

        $this->assertSame(0, GameVote::query()->count());
    }

    /**
     * Two people voting on the same game keep separate votes.
     */
    public function test_votes_are_per_visitor(): void
    {
        $game = Game::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('games.vote', $game), ['score' => 4]);
        $this->actingAs(User::factory()->create())->post(route('games.vote', $game), ['score' => 0]);

        $this->assertSame(2, GameVote::query()->count());
    }

    // Images

    public function test_a_game_screenshot_is_served(): void
    {
        // GameResourcesController reads through Storage::path(), i.e. the
        // default disk, so that is the one to fake.
        Storage::fake();

        $game = Game::factory()->create();
        $screenshot = Screenshot::factory()->create(['imgext' => 'png']);
        $game->screenshots()->attach($screenshot);

        Storage::put('public/' . $screenshot->getPath('game'), 'not really a png');

        $this->get(route('games.screenshot', [
            'game' => $game,
            'id'   => $screenshot->getKey(),
            'ext'  => 'png',
        ]))->assertOk();
    }

    /**
     * The id and the extension both have to match a screenshot the game
     * actually has, so a guessed URL cannot reach another game's image.
     */
    public function test_a_screenshot_of_another_game_is_a_404(): void
    {
        Storage::fake();

        $game = Game::factory()->create();
        $other = Game::factory()->create();

        $screenshot = Screenshot::factory()->create(['imgext' => 'png']);
        $other->screenshots()->attach($screenshot);

        Storage::put('public/' . $screenshot->getPath('game'), 'not really a png');

        $this->get(route('games.screenshot', [
            'game' => $game,
            'id'   => $screenshot->getKey(),
            'ext'  => 'png',
        ]))->assertNotFound();
    }

    public function test_the_wrong_extension_is_a_404(): void
    {
        Storage::fake();

        $game = Game::factory()->create();
        $screenshot = Screenshot::factory()->create(['imgext' => 'png']);
        $game->screenshots()->attach($screenshot);

        $this->get(route('games.screenshot', [
            'game' => $game,
            'id'   => $screenshot->getKey(),
            'ext'  => 'gif',
        ]))->assertNotFound();
    }
}
