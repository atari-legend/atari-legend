<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\MenuSetController;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The menu set listing, the per-set page and the software page.
 *
 * SearchDialectTest already covers the A-Z filter and the disk counts on the
 * index; this picks up the set page, the software page and the search.
 */
class MenuSetPagesTest extends TestCase
{
    use RefreshDatabase;

    private function setWithDisks(string $name, int $disks = 1, int $damaged = 0): MenuSet
    {
        $set = MenuSet::factory()->create(['name' => $name]);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => 1]);

        for ($i = 0; $i < $disks; $i++) {
            $factory = $i < $damaged ? MenuDisk::factory()->damaged() : MenuDisk::factory();
            $factory->create(['menu_id' => $menu->getKey(), 'part' => chr(65 + $i)]);
        }

        return $set->fresh();
    }

    public function test_a_set_page_lists_its_disks(): void
    {
        $set = $this->setWithDisks('Automation', disks: 2);

        $response = $this->get(route('menus.show', $set))->assertOk()->assertSee('Automation');

        $this->assertCount(2, $response->viewData('disks'));
    }

    public function test_a_set_page_counts_missing_disks_and_scrolltexts(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => 1]);

        MenuDisk::factory()->withScrolltext('Greetings')->create([
            'menu_id' => $menu->getKey(), 'part' => 'A',
        ]);
        MenuDisk::factory()->damaged()->create(['menu_id' => $menu->getKey(), 'part' => 'B']);

        $response = $this->get(route('menus.show', $set))->assertOk();

        $this->assertSame(1, $response->viewData('missingCount'));
        $this->assertSame(1, $response->viewData('scrollTextCount'));
    }

    /**
     * The set page paginates at 20 disks, which is what the page numbers in
     * menu links are based on.
     */
    public function test_a_set_page_paginates_at_twenty_disks(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);

        foreach (range(1, 25) as $number) {
            $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => $number]);
            MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => 'A']);
        }

        $disks = $this->get(route('menus.show', $set))->assertOk()->viewData('disks');

        $this->assertCount(MenuSetController::PAGE_SIZE, $disks);
        $this->assertSame(25, $disks->total());
    }

    public function test_an_unknown_set_is_a_404(): void
    {
        $this->get(route('menus.show', 9999))->assertNotFound();
    }

    /**
     * The software page lists the disks the software appears on, once each even
     * if it appears twice on one disk.
     */
    public function test_a_software_page_lists_its_disks_once(): void
    {
        $software = MenuSoftware::factory()->named('Xtracker')->create();
        $set = $this->setWithDisks('Automation');
        $disk = $set->menus->first()->disks->first();

        foreach ([1, 2] as $order) {
            MenuDiskContent::forceCreate([
                'menu_disk_id'     => $disk->getKey(),
                'order'            => $order,
                'menu_software_id' => $software->getKey(),
            ]);
        }

        $response = $this->get(route('menus.software', $software))->assertOk()->assertSee('Xtracker');

        $this->assertCount(1, $response->viewData('menuDisks'));
    }

    public function test_the_menu_search_finds_software_and_games_by_title(): void
    {
        MenuSoftware::factory()->named('Xtracker')->create();
        Game::factory()->named('Xenon')->create();
        MenuSoftware::factory()->named('Noisetracker')->create();

        $response = $this->get(route('menus.search', ['title' => 'Xtra']))->assertOk();

        $this->assertSame(['Xtracker'], $response->viewData('software')->pluck('name')->all());
        $this->assertSame([], $response->viewData('games')->pluck('game_name')->all());
    }

    /**
     * As with the game search, an empty form must not list everything.
     */
    public function test_the_menu_search_with_no_criteria_returns_nothing(): void
    {
        MenuSoftware::factory()->named('Xtracker')->create();
        Game::factory()->named('Xenon')->create();

        $response = $this->get(route('menus.search'))->assertOk();

        $this->assertCount(0, $response->viewData('software'));
        $this->assertCount(0, $response->viewData('games'));
    }

    /**
     * A game reached through a menu disk still shows up in the menu search.
     */
    public function test_the_menu_search_finds_games_by_alternative_title(): void
    {
        $game = Game::factory()->named('Bubble Bobble')->create();
        \Illuminate\Support\Facades\DB::table('game_aka')->insert([
            'game_id'  => $game->getKey(),
            'aka_name' => 'Baburu Boburu',
        ]);

        $response = $this->get(route('menus.search', ['title' => 'Baburu']))->assertOk();

        $this->assertSame(['Bubble Bobble'], $response->viewData('games')->pluck('game_name')->all());
    }

    /**
     * The scrolltext EPUB is generated on the fly and downloaded.
     *
     * The cover is drawn with imagettftext(), which needs GD built with
     * FreeType. The php.dockerfile in the development environment installs GD
     * without it, so this skips there rather than reporting a failure that is
     * about the container and not the code.
     */
    public function test_the_scrolltext_epub_is_downloadable(): void
    {
        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD was built without FreeType, so the EPUB cover cannot be drawn.');
        }

        $set = MenuSet::factory()->create(['name' => 'Automation']);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => 1]);
        MenuDisk::factory()->withScrolltext('Greetings to all our friends')->create([
            'menu_id' => $menu->getKey(),
            'part'    => 'A',
        ]);

        $response = $this->get(route('menus.epub', $set));

        $response->assertOk();
        $this->assertStringContainsString('epub', strtolower($response->headers->get('Content-Type')));
    }

    /**
     * A release listed on a menu disk links back to the game it belongs to.
     */
    public function test_a_disk_lists_the_releases_on_it(): void
    {
        $set = $this->setWithDisks('Automation');
        $disk = $set->menus->first()->disks->first();

        $game = Game::factory()->named('Xenon')->create();
        $release = GameRelease::factory()->create(['game_id' => $game->getKey()]);

        MenuDiskContent::forceCreate([
            'menu_disk_id'    => $disk->getKey(),
            'order'           => 1,
            'game_release_id' => $release->getKey(),
        ]);

        $this->get(route('menus.show', $set))
            ->assertOk()
            ->assertSee('Xenon');
    }
}
