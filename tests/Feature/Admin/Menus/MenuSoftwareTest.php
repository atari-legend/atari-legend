<?php

namespace Tests\Feature\Admin\Menus;

use App\Http\Controllers\MenuSetController;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\MenuDiskCondition;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The reference data behind menu disks: the software that can sit on a disk,
 * the content types that software is sorted into, and the conditions a disk can
 * be in.
 *
 * These are small lists, but everything else points at them - a content type is
 * what tells the software editor to warn about a game of the same name, and a
 * condition is what the public set listing counts as missing.
 */
class MenuSoftwareTest extends AdminTestCase
{
    // Software

    public function test_the_software_list_shows_the_software(): void
    {
        MenuSoftware::factory()->named('Xtracker')->create();

        $this->get(route('admin.menus.software.index'))
            ->assertOk()
            ->assertSee('Xtracker');
    }

    public function test_the_create_form_offers_the_content_types(): void
    {
        MenuSoftwareContentType::create(['name' => 'Cracktro']);

        $this->get(route('admin.menus.software.create'))
            ->assertOk()
            ->assertSee('Add a new menu software')
            ->assertSee('Cracktro');
    }

    public function test_the_edit_form_shows_the_software(): void
    {
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $this->get(route('admin.menus.software.edit', $software))
            ->assertOk()
            ->assertSee('Xtracker');
    }

    /**
     * Software typed as a game is a mistake waiting to happen: the menu should
     * point at the game record instead. The editor only goes looking for a
     * matching game for that one content type, so both halves matter.
     */
    public function test_the_edit_form_warns_when_a_game_of_the_same_name_exists(): void
    {
        $gameType = MenuSoftwareContentType::create(['name' => 'Game']);
        $otherType = MenuSoftwareContentType::create(['name' => 'Utility']);
        Game::factory()->named('Xenon')->create();

        $software = MenuSoftware::factory()->named('Xenon')->create([
            'menu_software_content_type_id' => $gameType->getKey(),
        ]);

        $this->get(route('admin.menus.software.edit', $software))
            ->assertOk()
            ->assertSee('with the same name');

        $software->update(['menu_software_content_type_id' => $otherType->getKey()]);

        $this->get(route('admin.menus.software.edit', $software))
            ->assertOk()
            ->assertDontSee('with the same name');
    }

    public function test_software_can_be_renamed_and_retyped(): void
    {
        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $this->put(route('admin.menus.software.update', $software), [
            'name'    => 'X-Tracker',
            'type'    => $type->getKey(),
            'demozoo' => 4321,
        ])->assertRedirect(route('admin.menus.software.index'));

        $software->refresh();

        $this->assertSame('X-Tracker', $software->name);
        $this->assertSame($type->getKey(), $software->menu_software_content_type_id);
        $this->assertSame(4321, $software->demozoo_id);

        // Left unnamed: the controller means to record the name the software
        // had before the edit, but reads it back after the save.
        $this->assertChangelog(Changelog::UPDATE, 'Menu Software');
    }

    // Content types

    public function test_the_content_type_list_shows_each_type_and_what_uses_it(): void
    {
        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);
        MenuSoftware::factory()->named('Xtracker')->create([
            'menu_software_content_type_id' => $type->getKey(),
        ]);

        $this->get(route('admin.menus.content-types.index'))
            ->assertOk()
            ->assertSee('Cracktro');
    }

    public function test_the_content_type_forms_render(): void
    {
        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);

        $this->get(route('admin.menus.content-types.create'))
            ->assertOk()
            ->assertSee('Add a new menu content-type');

        $this->get(route('admin.menus.content-types.edit', $type))
            ->assertOk()
            ->assertSee('Cracktro');
    }

    public function test_a_content_type_can_be_created(): void
    {
        $this->post(route('admin.menus.content-types.store'), ['name' => 'Cracktro'])
            ->assertRedirect(route('admin.menus.content-types.index'));

        $type = MenuSoftwareContentType::where('name', 'Cracktro')->sole();

        $this->assertChangelog(Changelog::INSERT, 'Menu Content Types', 'Cracktro');
        $this->assertSame($type->getKey(), Changelog::where('action', Changelog::INSERT)->sole()->section_id);
    }

    public function test_a_content_type_can_be_renamed(): void
    {
        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);

        $this->put(route('admin.menus.content-types.update', $type), ['name' => 'Crack intro'])
            ->assertRedirect(route('admin.menus.content-types.index'));

        $this->assertSame('Crack intro', $type->fresh()->name);
        $this->assertChangelog(Changelog::UPDATE, 'Menu Content Types');
    }

    public function test_a_content_type_can_be_deleted(): void
    {
        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);

        $this->delete(route('admin.menus.content-types.destroy', $type))
            ->assertRedirect(route('admin.menus.content-types.index'));

        $this->assertSame(0, MenuSoftwareContentType::where('name', 'Cracktro')->count());
        $this->assertChangelog(Changelog::DELETE, 'Menu Content Types', 'Cracktro');
    }

    // Conditions

    /**
     * The four conditions are reference data the migrations ship, and the disk
     * editor picks from them by id, so the list is expected to already hold
     * them on a fresh database.
     */
    public function test_the_condition_list_shows_the_conditions_shipped_with_the_database(): void
    {
        $this->get(route('admin.menus.conditions.index'))
            ->assertOk()
            ->assertSee('Intact')
            ->assertSee('Slightly damaged');
    }

    public function test_the_condition_forms_render(): void
    {
        $condition = MenuDiskCondition::findOrFail(MenuSetController::INTACT_CONDITION_ID);

        $this->get(route('admin.menus.conditions.create'))
            ->assertOk()
            ->assertSee('Add a new menu condition');

        $this->get(route('admin.menus.conditions.edit', $condition))
            ->assertOk()
            ->assertSee('Intact');
    }

    public function test_a_software_cannot_be_saved_with_an_empty_name(): void
    {
        $this->post(route('admin.menus.software.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $software = MenuSoftware::factory()->named('Xtracker')->create();
        $this->put(route('admin.menus.software.update', $software), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_a_content_type_cannot_be_saved_with_an_empty_name(): void
    {
        $this->post(route('admin.menus.content-types.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $type = MenuSoftwareContentType::create(['name' => 'Cracktro']);
        $this->put(route('admin.menus.content-types.update', $type), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_a_condition_cannot_be_saved_with_an_empty_name(): void
    {
        $this->post(route('admin.menus.conditions.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $condition = MenuDiskCondition::findOrFail(MenuSetController::INTACT_CONDITION_ID);
        $this->put(route('admin.menus.conditions.update', $condition), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_the_reference_data_editors_are_closed_to_non_admins(): void
    {
        $this->assertNonAdminIsTurnedAway(route('admin.menus.software.create'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.content-types.index'));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.conditions.index'));
    }
}
