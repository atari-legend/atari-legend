<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\EmulatorController;
use App\Models\Dump;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Media;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskDump;
use App\Models\MenuSet;
use App\Models\Screenshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The page a dump is played on in the emulator.
 */
class EmulatorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_release_page_links_each_dump_to_the_emulator(): void
    {
        $dump = Dump::factory()->create();
        $release = $dump->media->release;

        $this->get(route('games.releases.show', $release))
            ->assertOk()
            ->assertSee(route('games.releases.emulator', ['release' => $release, 'dump' => $dump]));
    }

    public function test_the_emulator_boots_the_dump_on_the_tos(): void
    {
        $dump = Dump::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertSee($dump->download_url)
            ->assertSee(asset('storage/' . EmulatorController::TOS));
    }

    public function test_the_emulator_offers_every_disk_of_the_release(): void
    {
        $first = Dump::factory()->create();
        $release = $first->media->release;
        $secondMedia = Media::factory()->create(['game_release_id' => $release->getKey(), 'label' => 'Disk 2']);
        $second = Dump::factory()->create(['media_id' => $secondMedia->getKey()]);

        $this->get(route('games.releases.emulator', ['release' => $release, 'dump' => $first]))
            ->assertOk()
            ->assertSeeInOrder([$first->media->label, 'Disk 2'])
            ->assertSee('data-emulator-disk="' . $second->getKey() . '"', false)
            ->assertSee($second->download_url);
    }

    public function test_dumps_of_the_same_disk_are_told_apart_by_their_format(): void
    {
        $stx = Dump::factory()->create(['format' => 'stx']);
        Dump::factory()->create(['media_id' => $stx->media_id, 'format' => 'msa']);

        $this->get(route('games.releases.emulator', ['release' => $stx->media->release, 'dump' => $stx]))
            ->assertOk()
            ->assertSee($stx->media->label . ' (STX)')
            ->assertSee($stx->media->label . ' (MSA)');
    }

    public function test_a_release_on_a_single_disk_has_no_disk_buttons(): void
    {
        $dump = Dump::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertDontSee('data-emulator-disk', false);
    }

    public function test_a_dump_is_not_played_under_another_release(): void
    {
        $dump = Dump::factory()->create();
        $otherRelease = GameRelease::factory()->create();

        $this->get(route('games.releases.emulator', ['release' => $otherRelease, 'dump' => $dump]))
            ->assertNotFound();
    }

    public function test_the_game_card_beside_the_emulator_does_not_offer_to_play(): void
    {
        $dump = Dump::factory()->create();
        $game = $dump->media->release->game;
        $game->screenshots()->attach(Screenshot::factory()->create());

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertSee('Screenshot of ' . $game->name)
            ->assertDontSee('play-screenshot', false);
    }

    private function dumpOn(Game $game, string $date, ?string $type = null): Dump
    {
        $release = GameRelease::factory()->create(['game_id' => $game->getKey(), 'date' => $date, 'type' => $type]);
        $media = Media::factory()->create(['game_release_id' => $release->getKey()]);

        return Dump::factory()->create(['media_id' => $media->getKey()]);
    }

    private function emulatorUrl(Dump $dump): string
    {
        return route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]);
    }

    public function test_a_game_plays_its_earliest_dumped_release_whatever_its_type(): void
    {
        $game = Game::factory()->create();
        $this->dumpOn($game, '1991-01-01');
        $demo = $this->dumpOn($game, '1990-01-01', 'Playable demo');
        GameRelease::factory()->create(['game_id' => $game->getKey(), 'date' => '1989-01-01']);

        $this->assertSame($this->emulatorUrl($demo), $game->emulator_url);
    }

    public function test_a_game_without_a_dump_plays_nothing(): void
    {
        $release = GameRelease::factory()->create();
        Media::factory()->create(['game_release_id' => $release->getKey()]);

        $this->assertNull($release->game->emulator_url);
    }

    public function test_a_release_plays_its_first_disk(): void
    {
        $first = Dump::factory()->create();
        $release = $first->media->release;
        $secondMedia = Media::factory()->create(['game_release_id' => $release->getKey(), 'label' => 'Disk 2']);
        Dump::factory()->create(['media_id' => $secondMedia->getKey()]);

        $this->assertSame($this->emulatorUrl($first), $release->fresh()->emulator_url);
    }

    private function dumpedMenuDisk(string $part, ?Menu $menu = null): MenuDisk
    {
        $menu ??= Menu::factory()->create(['number' => 159, 'version' => null]);
        $disk = MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => $part]);
        MenuDiskDump::factory()->create(['menu_disk_id' => $disk->getKey()]);

        return $disk->fresh();
    }

    public function test_a_menu_disk_boots_on_the_tos(): void
    {
        $disk = $this->dumpedMenuDisk('A');

        $this->get(route('menus.emulator', ['set' => $disk->menu->menuSet, 'disk' => $disk]))
            ->assertOk()
            ->assertSee($disk->menuDiskDump->download_url)
            ->assertSee(asset('storage/' . EmulatorController::TOS));
    }

    public function test_a_menu_on_several_disks_offers_its_dumped_disks(): void
    {
        $first = $this->dumpedMenuDisk('A');
        $second = $this->dumpedMenuDisk('B', $first->menu);
        MenuDisk::factory()->create(['menu_id' => $first->menu_id, 'part' => 'C']);

        $response = $this->get(route('menus.emulator', ['set' => $second->menu->menuSet, 'disk' => $second]))
            ->assertOk()
            ->assertSee('data-disk-id="' . $second->menuDiskDump->getKey() . '"', false)
            ->assertSeeInOrder(['#159A', '#159B']);

        $this->assertSame(2, substr_count($response->getContent(), 'data-emulator-disk='));
    }

    public function test_a_menu_disk_is_not_played_under_another_set(): void
    {
        $disk = $this->dumpedMenuDisk('A');

        $this->get(route('menus.emulator', ['set' => MenuSet::factory()->create(), 'disk' => $disk]))
            ->assertNotFound();
    }

    public function test_a_menu_disk_without_a_dump_is_not_found(): void
    {
        $disk = MenuDisk::factory()->create();

        $this->get(route('menus.emulator', ['set' => $disk->menu->menuSet, 'disk' => $disk]))
            ->assertNotFound();
    }

    public function test_the_emulator_credits_its_hatari_build(): void
    {
        $dump = Dump::factory()->create();
        $disk = $this->dumpedMenuDisk('A');

        $this->get(route('games.releases.emulator', ['release' => $dump->media->release, 'dump' => $dump]))
            ->assertOk()
            ->assertSee('http://absencehq.de/atariaviary/');

        $this->get(route('menus.emulator', ['set' => $disk->menu->menuSet, 'disk' => $disk]))
            ->assertOk()
            ->assertSee('http://absencehq.de/atariaviary/');
    }
}
