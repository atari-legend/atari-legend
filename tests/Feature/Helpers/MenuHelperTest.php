<?php

namespace Tests\Feature\Helpers;

use App\Helpers\MenuHelper;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The completion figure and the meta description shown for a menu set.
 */
class MenuHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_completion_is_the_share_of_disks_present(): void
    {
        $this->assertSame(100.0, MenuHelper::percentComplete(disks: 4, missing: 0));
        $this->assertSame(75.0, MenuHelper::percentComplete(disks: 4, missing: 1));
        $this->assertSame(0.0, MenuHelper::percentComplete(disks: 4, missing: 4));
    }

    private function set(string $name, int $disks, int $withScrolltext = 0): MenuSet
    {
        $set = MenuSet::factory()->create(['name' => $name]);
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey()]);

        for ($i = 0; $i < $disks; $i++) {
            $factory = MenuDisk::factory();

            if ($i < $withScrolltext) {
                $factory = $factory->withScrolltext('Greetings to all our friends');
            }

            $factory->create(['menu_id' => $menu->getKey(), 'part' => chr(65 + $i)]);
        }

        return $set->fresh();
    }

    public function test_the_description_counts_disks_and_scrolltexts(): void
    {
        $this->assertSame(
            'Atari ST menu set Automation: 3 disks, 1 missing, 2 scrolltexts.',
            MenuHelper::description($this->set('Automation', disks: 3, withScrolltext: 2), 1)
        );
    }

    /**
     * Both counts are pluralised, and a set can legitimately have exactly one
     * of either.
     */
    public function test_single_disks_and_scrolltexts_are_singular(): void
    {
        $this->assertSame(
            'Atari ST menu set Medway Boys: 1 disk, 0 missing, 1 scrolltext.',
            MenuHelper::description($this->set('Medway Boys', disks: 1, withScrolltext: 1), 0)
        );
    }

    public function test_a_set_with_no_scrolltexts_says_zero(): void
    {
        $this->assertSame(
            'Atari ST menu set Superior: 2 disks, 0 missing, 0 scrolltexts.',
            MenuHelper::description($this->set('Superior', disks: 2), 0)
        );
    }

    /**
     * Disks are gathered across every menu in the set, not just the first.
     */
    public function test_disks_are_counted_across_all_menus(): void
    {
        $set = MenuSet::factory()->create(['name' => 'Automation']);

        foreach ([1, 2] as $number) {
            $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => $number]);
            MenuDisk::factory()->create(['menu_id' => $menu->getKey(), 'part' => 'A']);
        }

        $this->assertStringContainsString('2 disks', MenuHelper::description($set->fresh(), 0));
    }
}
