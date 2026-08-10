<?php

namespace Tests\Feature\Admin\Menus;

use App\Http\Controllers\MenuSetController;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Game;
use App\Models\Individual;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuDiskScreenshot;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use App\Models\Release;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The admin Menus section: sets, the menus inside them, the disks inside those,
 * and what is on each disk.
 *
 * The hierarchy is the point - a disk only makes sense through its menu and
 * set, and the changelog names it by a label built from all three.
 */
class MenuAdminTest extends AdminTestCase
{
    private function set(string $name = 'Automation'): MenuSet
    {
        return MenuSet::factory()->create(['name' => $name]);
    }

    private function menu(?MenuSet $set = null, int $number = 1): Menu
    {
        return Menu::factory()->create([
            'menu_set_id' => ($set ?? $this->set())->getKey(),
            'number'      => $number,
        ]);
    }

    private function disk(?Menu $menu = null, string $part = 'A'): MenuDisk
    {
        return MenuDisk::factory()->create([
            'menu_id' => ($menu ?? $this->menu())->getKey(),
            'part'    => $part,
        ]);
    }

    // Sets

    public function test_the_set_list_can_be_filtered_by_letter(): void
    {
        $this->set('Automation');
        $this->set('9 Fingers');

        $this->get(route('admin.menus.sets.index', ['letter' => 'A']))
            ->assertOk()
            ->assertSee('Automation')
            ->assertDontSee('9 Fingers');

        $this->get(route('admin.menus.sets.index', ['letter' => '0-9']))
            ->assertOk()
            ->assertSee('9 Fingers')
            ->assertDontSee('Automation');
    }

    public function test_a_set_can_be_created_with_its_crews(): void
    {
        $crew = Crew::factory()->create(['crew_name' => 'The Replicants']);

        $this->post(route('admin.menus.sets.store'), [
            'name'  => 'Automation',
            'sort'  => 'asc',
            'crews' => [$crew->getKey()],
        ])->assertRedirect(route('admin.menus.sets.edit', MenuSet::sole()));

        $set = MenuSet::sole();

        $this->assertSame('Automation', $set->name);
        $this->assertSame('asc', $set->menus_sort);
        $this->assertSame(['The Replicants'], $set->crews->pluck('crew_name')->all());
        $this->assertChangelog(Changelog::INSERT, 'Menus', 'Automation');
    }

    public function test_a_set_needs_a_name_a_sort_and_a_crew(): void
    {
        $this->post(route('admin.menus.sets.store'), [])
            ->assertSessionHasErrors(['name', 'sort', 'crews']);

        $this->assertSame(0, MenuSet::query()->count());
        $this->assertNoChangelog();
    }

    public function test_a_set_can_be_renamed_and_resorted(): void
    {
        $set = $this->set('Automation');
        $crew = Crew::factory()->create();

        $this->put(route('admin.menus.sets.update', $set), [
            'name'  => 'Automation Menus',
            'sort'  => 'desc',
            'crews' => [$crew->getKey()],
        ])->assertRedirect();

        $set->refresh();

        $this->assertSame('Automation Menus', $set->name);
        $this->assertSame('desc', $set->menus_sort);
    }

    public function test_a_set_can_be_deleted(): void
    {
        $set = $this->set('Automation');

        $this->delete(route('admin.menus.sets.destroy', $set))
            ->assertRedirect(route('admin.menus.sets.index'));

        $this->assertSame(0, MenuSet::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Menus', 'Automation');
    }

    // Menus

    public function test_a_menu_can_be_added_to_a_set(): void
    {
        $set = $this->set('Automation');

        $this->post(route('admin.menus.menus.store'), [
            'set'     => $set->getKey(),
            'number'  => 189,
            'version' => '1.0',
            'issue'   => null,
            'date'    => null,
        ])->assertRedirect(route('admin.menus.menus.edit', Menu::sole()));

        $menu = Menu::sole();

        $this->assertSame(189, $menu->number);
        $this->assertSame('1.0', $menu->version);
        $this->assertSame($set->getKey(), $menu->menu_set_id);
    }

    public function test_a_menu_can_be_edited_and_deleted(): void
    {
        $menu = $this->menu();

        $this->put(route('admin.menus.menus.update', $menu), [
            'number'  => 190,
            'version' => '2.0',
        ])->assertRedirect();

        $this->assertSame(190, $menu->fresh()->number);

        $this->delete(route('admin.menus.menus.destroy', $menu))->assertRedirect();

        $this->assertSame(0, Menu::query()->count());
    }

    // Disks

    /**
     * The label is built from the number, the version and the part, and is
     * what names a disk in the changelog and in its download filename.
     */
    public function test_a_menu_label_reads_cleanly(): void
    {
        $set = $this->set('Automation');

        $numbered = Menu::factory()->create([
            'menu_set_id' => $set->getKey(),
            'number'      => 189,
            'version'     => '1.0',
        ]);
        $this->assertSame('#189 v1.0', $numbered->label);

        $unversioned = Menu::factory()->create([
            'menu_set_id' => $set->getKey(),
            'number'      => 190,
            'version'     => null,
        ]);
        $this->assertSame('#190', $unversioned->label);

        $unnumbered = Menu::factory()->create([
            'menu_set_id' => $set->getKey(),
            'number'      => null,
            'issue'       => 'Christmas',
            'version'     => null,
        ]);
        $this->assertSame('Christmas', $unnumbered->label);

        $this->assertSame('Automation #189 v1.0', $numbered->full_label);

        // And the label a disk downloads as, which the extra space showed up in
        $disk = MenuDisk::factory()->create(['menu_id' => $numbered->getKey(), 'part' => 'A']);
        $this->assertSame('Automation #189 v1.0A', $disk->download_basename);
    }

    public function test_a_disk_can_be_added_to_a_menu(): void
    {
        $menu = Menu::factory()->create([
            'menu_set_id' => $this->set('Automation')->getKey(),
            'number'      => 189,
            'version'     => null,
        ]);

        $this->post(route('admin.menus.disks.store'), [
            'menu'       => $menu->getKey(),
            'part'       => 'A',
            'condition'  => MenuSetController::INTACT_CONDITION_ID,
            'scrolltext' => 'Greetings to all our friends',
            'notes'      => 'Side A',
        ])->assertRedirect(route('admin.menus.disks.edit', MenuDisk::sole()));

        $disk = MenuDisk::sole();

        $this->assertSame('A', $disk->part);
        $this->assertSame('Greetings to all our friends', $disk->scrolltext);
        $this->assertSame(MenuSetController::INTACT_CONDITION_ID, $disk->menu_disk_condition_id);

        // The changelog names the disk by set, menu and part together
        $this->assertChangelog(Changelog::INSERT, 'Menu Disks', 'Automation #189A');
    }

    public function test_a_disk_needs_a_condition(): void
    {
        $menu = $this->menu();

        $this->post(route('admin.menus.disks.store'), ['menu' => $menu->getKey(), 'part' => 'A'])
            ->assertSessionHasErrors('condition');

        $this->assertSame(0, MenuDisk::query()->count());
    }

    public function test_a_disk_can_record_who_donated_it(): void
    {
        $menu = $this->menu();
        $donor = Individual::factory()->create(['ind_name' => 'Someone']);

        $this->post(route('admin.menus.disks.store'), [
            'menu'      => $menu->getKey(),
            'part'      => 'A',
            'condition' => MenuSetController::INTACT_CONDITION_ID,
            'donated'   => $donor->getKey(),
        ])->assertRedirect();

        $this->assertSame('Someone', MenuDisk::sole()->donatedBy->ind_name);
    }

    public function test_a_disk_can_be_edited_and_deleted(): void
    {
        $disk = $this->disk();

        $this->put(route('admin.menus.disks.update', $disk), [
            'condition'  => 1,
            'part'       => 'B',
            'scrolltext' => 'Rewritten',
        ])->assertRedirect();

        $disk->refresh();

        $this->assertSame('B', $disk->part);
        $this->assertSame(1, $disk->menu_disk_condition_id);

        $this->delete(route('admin.menus.disks.destroy', $disk))->assertRedirect();

        $this->assertSame(0, MenuDisk::query()->count());
    }

    public function test_a_disk_screenshot_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeScreenshot', $disk), [
            'screenshot' => UploadedFile::fake()->image('menu.png'),
        ])->assertRedirect(route('admin.menus.disks.edit', $disk));

        $screenshot = MenuDiskScreenshot::sole();
        Storage::disk('public')->assertExists('images/menu_screenshots/' . $screenshot->file);

        $this->delete(route('admin.menus.disks.destroyScreenshot', [$disk, $screenshot]))
            ->assertRedirect(route('admin.menus.disks.edit', $disk));

        $this->assertSame(0, MenuDiskScreenshot::query()->count());
        Storage::disk('public')->assertMissing('images/menu_screenshots/' . $screenshot->file);
    }

    // Disk contents

    public function test_software_can_be_put_on_a_disk(): void
    {
        $disk = $this->disk();
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $this->post(route('admin.menus.disks.content.store', $disk), [
            'disk'     => $disk->getKey(),
            'type'     => 'software',
            'order'    => 1,
            'software' => $software->getKey(),
        ])->assertRedirect();

        $content = MenuDiskContent::sole();

        $this->assertSame($software->getKey(), $content->menu_software_id);
        $this->assertSame(1, $content->order);
    }

    public function test_a_game_can_be_put_on_a_disk(): void
    {
        $disk = $this->disk();
        $game = Game::factory()->named('Xenon')->create();

        $this->post(route('admin.menus.disks.content.store', $disk), [
            'disk'    => $disk->getKey(),
            'type'    => 'game',
            'order'   => 1,
            'game'    => $game->getKey(),
            'subtype' => 'Game',
        ])->assertRedirect();

        $this->assertSame($game->getKey(), MenuDiskContent::sole()->game_id);
    }

    public function test_an_existing_release_can_be_put_on_a_disk(): void
    {
        $disk = $this->disk();
        $release = Release::factory()->create();

        $this->post(route('admin.menus.disks.content.store', $disk), [
            'disk'    => $disk->getKey(),
            'type'    => 'release',
            'action'  => 'use-release',
            'order'   => 1,
            'release' => $release->getKey(),
            'subtype' => 'Game',
        ])->assertRedirect();

        $this->assertSame($release->getKey(), MenuDiskContent::sole()->game_release_id);
    }

    public function test_disk_content_needs_an_order_and_its_own_fields(): void
    {
        $disk = $this->disk();

        $this->post(route('admin.menus.disks.content.store', $disk), [
            'disk' => $disk->getKey(),
            'type' => 'software',
        ])->assertSessionHasErrors(['order', 'software']);

        $this->assertSame(0, MenuDiskContent::query()->count());
    }

    /**
     * The type decides which fields are required, so an unknown one is a
     * programming error rather than a validation failure.
     */
    public function test_an_unknown_content_type_throws(): void
    {
        $disk = $this->disk();

        $this->withoutExceptionHandling();
        $this->expectException(\ErrorException::class);

        $this->post(route('admin.menus.disks.content.store', $disk), [
            'disk'  => $disk->getKey(),
            'type'  => 'nonsense',
            'order' => 1,
        ]);
    }

    // Software, crews and reference data

    public function test_software_can_be_created_and_deleted(): void
    {
        $type = MenuSoftwareContentType::query()->first();

        $this->post(route('admin.menus.software.store'), [
            'name'    => 'Xtracker',
            'type'    => $type->getKey(),
            'demozoo' => 1234,
        ])->assertRedirect(route('admin.menus.software.index'));

        $software = MenuSoftware::sole();

        $this->assertSame('Xtracker', $software->name);
        $this->assertSame(1234, $software->demozoo_id);

        // The section used to be written as 'Menu Softwre'. Entries from before
        // the fix still say that, and AdminStatisticsHelper folds them, but
        // nothing should write it any more.
        $this->assertChangelog(Changelog::INSERT, 'Menu Software', 'Xtracker');
        $this->assertSame(0, Changelog::where('section', 'Menu Softwre')->count());

        $this->delete(route('admin.menus.software.destroy', $software))->assertRedirect();

        $this->assertSame(0, MenuSoftware::query()->count());
    }

    public function test_a_crew_can_be_created_and_given_members(): void
    {
        $this->post(route('admin.menus.crews.store'), ['name' => 'The Replicants'])
            ->assertRedirect();

        $crew = Crew::sole();
        $this->assertSame('The Replicants', $crew->crew_name);

        $individual = Individual::factory()->create(['ind_name' => 'Someone']);

        $this->post(route('admin.menus.crews.addIndividual', $crew), [
            'individual' => $individual->getKey(),
        ])->assertRedirect();

        $this->assertSame(['Someone'], $crew->fresh()->individuals->pluck('ind_name')->all());

        $this->delete(route('admin.menus.crews.removeIndividual', [$crew, $individual]))
            ->assertRedirect();

        $this->assertCount(0, $crew->fresh()->individuals);
    }

    public function test_a_crew_needs_a_name(): void
    {
        $this->post(route('admin.menus.crews.store'), [])->assertSessionHasErrors('name');

        $this->assertSame(0, Crew::query()->count());
    }

    public function test_sub_crews_can_be_linked_and_unlinked(): void
    {
        $parent = Crew::factory()->create(['crew_name' => 'The Replicants']);
        $child = Crew::factory()->create(['crew_name' => 'Replicants Junior']);

        $this->post(route('admin.menus.crews.addSubCrew', $parent), ['subcrew' => $child->getKey()])
            ->assertRedirect();

        $this->assertSame(['Replicants Junior'], $parent->fresh()->subCrews->pluck('crew_name')->all());

        $this->delete(route('admin.menus.crews.removeSubCrew', [$parent, $child]))->assertRedirect();

        $this->assertCount(0, $parent->fresh()->subCrews);
    }

    public function test_conditions_and_content_types_can_be_managed(): void
    {
        $this->post(route('admin.menus.conditions.store'), ['name' => 'Partly readable'])
            ->assertRedirect(route('admin.menus.conditions.index'));

        $condition = MenuDiskCondition::where('name', 'Partly readable')->sole();

        $this->put(route('admin.menus.conditions.update', $condition), ['name' => 'Barely readable'])
            ->assertRedirect();
        $this->assertSame('Barely readable', $condition->fresh()->name);

        $this->delete(route('admin.menus.conditions.destroy', $condition))->assertRedirect();
        $this->assertSame(0, MenuDiskCondition::where('name', 'Barely readable')->count());

        $this->post(route('admin.menus.content-types.store'), ['name' => 'Cracktro'])
            ->assertRedirect(route('admin.menus.content-types.index'));

        $this->assertSame(1, MenuSoftwareContentType::where('name', 'Cracktro')->count());
    }

    public function test_the_menu_admin_is_closed_to_non_admins(): void
    {
        $set = $this->set();

        $this->assertNonAdminIsTurnedAway(route('admin.menus.sets.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.sets.edit', $set));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.crews.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.software.index'));
    }
}
