<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\MenuSetController;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The game and menu searches used to rely on `regexp`, `YEAR()` and
 * `convert(..., unsigned integer)`, all of which are MySQL-only. These tests
 * run on SQLite, so they fail if any of that creeps back in.
 *
 * Note that each search page only renders its own kind of result - games on
 * games.search, software on menus.search - so the other collection is checked
 * through the view data.
 */
class SearchDialectTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $name): Game
    {
        $game = new Game();
        $game->name = $name;
        $game->slug = Str::slug($name) ?: 'game-' . mt_rand();
        $game->save();

        return $game;
    }

    private function software(string $name): MenuSoftware
    {
        return MenuSoftware::create([
            'name'                          => $name,
            'menu_software_content_type_id' => MenuSoftwareContentType::create(['name' => 'Ripper'])->id,
        ]);
    }

    /**
     * Seed two titles starting with a letter and two starting with a digit, so
     * both branches of the A-Z filter have something to include and exclude.
     */
    private function seedTitles(): void
    {
        $this->game('Xenon');
        $this->game('1943');
        $this->software('Xtracker');
        $this->software('4-Mat Ripper');
    }

    public function testGameSearchByLetter()
    {
        $this->seedTitles();

        $response = $this->get(route('games.search', ['titleAZ' => 'X']))
            ->assertOk()
            ->assertSee('Xenon')
            ->assertDontSee('1943');

        $this->assertSame(['Xenon'], $response->viewData('games')->pluck('name')->all());
        $this->assertSame(['Xtracker'], $response->viewData('software')->pluck('name')->all());
    }

    public function testGameSearchByDigit()
    {
        $this->seedTitles();

        $response = $this->get(route('games.search', ['titleAZ' => '0-9']))
            ->assertOk()
            ->assertSee('1943')
            ->assertDontSee('Xenon');

        $this->assertSame(['1943'], $response->viewData('games')->pluck('name')->all());
        $this->assertSame(['4-Mat Ripper'], $response->viewData('software')->pluck('name')->all());
    }

    public function testMenuSearchByLetter()
    {
        $this->seedTitles();

        $response = $this->get(route('menus.search', ['titleAZ' => 'X']))
            ->assertOk()
            ->assertSee('Xtracker')
            ->assertDontSee('4-Mat Ripper');

        $this->assertSame(['Xtracker'], $response->viewData('software')->pluck('name')->all());
        $this->assertSame(['Xenon'], $response->viewData('games')->pluck('name')->all());
    }

    public function testMenuSearchByDigit()
    {
        $this->seedTitles();

        $response = $this->get(route('menus.search', ['titleAZ' => '0-9']))
            ->assertOk()
            ->assertSee('4-Mat Ripper')
            ->assertDontSee('Xtracker');

        $this->assertSame(['4-Mat Ripper'], $response->viewData('software')->pluck('name')->all());
        $this->assertSame(['1943'], $response->viewData('games')->pluck('name')->all());
    }

    public function testSearchFormListsReleaseYears()
    {
        $game = $this->game('Xenon');

        foreach (['1988-06-01', '1989-01-15'] as $date) {
            $release = new GameRelease();
            $release->game_id = $game->getKey();
            $release->date = $date;
            $release->save();
        }

        // getSearchReferenceData() derives the year list from the release date
        $years = $this->get(route('games.index'))
            ->assertOk()
            ->viewData('years');

        $this->assertSame(['1988', '1989'], $years->pluck('year')->all());
    }

    public function testMenuSetIndexCountsDisks()
    {
        $set = MenuSet::create(['name' => 'Automation', 'menus_sort' => 'asc']);
        $menu = Menu::create(['number' => 1, 'version' => '1.0', 'menu_set_id' => $set->id]);

        // Two disks, only one of them intact, so `missing` must come out as 1
        MenuDisk::create([
            'menu_id'                => $menu->id,
            'part'                   => 'A',
            'menu_disk_condition_id' => MenuSetController::INTACT_CONDITION_ID,
        ]);
        MenuDisk::create([
            'menu_id'                => $menu->id,
            'part'                   => 'B',
            'menu_disk_condition_id' => 1,
        ]);

        $sets = $this->get(route('menus.index'))
            ->assertOk()
            ->assertSee('Automation')
            ->viewData('menusets');

        // Strictly ints: menus/card_list.blade.php compares `missing` with ===
        $this->assertSame(2, $sets->first()->disks);
        $this->assertSame(1, $sets->first()->missing);
    }
}
