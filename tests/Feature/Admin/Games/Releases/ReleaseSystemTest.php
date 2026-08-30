<?php

namespace Tests\Feature\Admin\Games\Releases;

use App\Models\Changelog;
use App\Models\CopyProtection;
use App\Models\DiskProtection;
use App\Models\Emulator;
use App\Models\Enhancement;
use App\Models\GameRelease;
use App\Models\Language;
use App\Models\Memory;
use App\Models\GameReleaseMemoryEnhanced;
use App\Models\GameReleaseSystemEnhanced;
use App\Models\GameReleaseTosVersionIncompatibility;
use App\Models\Resolution;
use App\Models\System;
use App\Models\Tos;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The system info panel of a release, and the six small editors that sit on the
 * same page: system enhancements, incompatible TOS versions, memory, memory
 * enhancements, and the two kinds of protection.
 *
 * Everything here is a pivot row, and the panels differ in how they are saved -
 * the top of the page rewrites its lists wholesale on every save, while the
 * panels below it add and delete a row at a time. The delete tests therefore
 * always leave a second row in place, to show that the right one went.
 */
class ReleaseSystemTest extends AdminTestCase
{
    // System info

    public function test_the_system_panel_loads(): void
    {
        $release = GameRelease::factory()
            ->inResolutions('Low')
            ->incompatibleWithSystems('Falcon')
            ->copyProtectedBy('Code wheel')
            ->create();

        $this->get(route('admin.games.releases.system.index', [$release->game, $release]))
            ->assertOk()
            ->assertSee('Low')
            ->assertSee('Falcon')
            ->assertSee('Code wheel');
    }

    public function test_resolutions_systems_and_emulators_are_attached(): void
    {
        $release = GameRelease::factory()->create();
        $resolution = Resolution::factory()->create(['name' => 'Low']);
        $system = System::factory()->create(['name' => 'Falcon']);
        $emulator = Emulator::factory()->create(['name' => 'Hatari']);

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]), [
            'resolutions' => [$resolution->getKey()],
            'systems'     => [$system->getKey()],
            'emulators'   => [$emulator->getKey()],
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $release->refresh();

        $this->assertSame(['Low'], $release->resolutions->pluck('name')->all());
        $this->assertSame(['Falcon'], $release->systemIncompatibles->pluck('name')->all());
        $this->assertSame(['Hatari'], $release->emulatorIncompatibles->pluck('name')->all());
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->game_name);
    }

    /**
     * The HD flag is a checkbox, so an unticked box posts nothing at all rather
     * than a false - the release has to end up not HD installable either way.
     */
    public function test_the_hd_installable_flag_is_set_and_cleared(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]), [
            'hd' => 'true',
        ])->assertRedirect();

        $this->assertTrue($release->fresh()->hd_installable);

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]))
            ->assertRedirect();

        $this->assertFalse($release->fresh()->hd_installable);
    }

    public function test_saving_an_empty_panel_detaches_the_lists(): void
    {
        $release = GameRelease::factory()
            ->inResolutions('Low')
            ->incompatibleWithSystems('Falcon')
            ->incompatibleWithEmulators('Hatari')
            ->create();

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]))
            ->assertRedirect();

        $release->refresh();

        $this->assertCount(0, $release->resolutions);
        $this->assertCount(0, $release->systemIncompatibles);
        $this->assertCount(0, $release->emulatorIncompatibles);
    }

    public function test_the_system_lists_must_be_posted_as_lists(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]), [
            'resolutions' => Resolution::factory()->create()->getKey(),
            'systems'     => System::factory()->create()->getKey(),
            'emulators'   => Emulator::factory()->create()->getKey(),
        ])->assertSessionHasErrors(['resolutions', 'systems', 'emulators']);

        $this->assertCount(0, $release->fresh()->resolutions);
        $this->assertNoChangelog();
    }

    public function test_an_unknown_resolution_is_a_404(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system.update', [$release->game, $release]), [
            'resolutions' => [9999],
        ])->assertNotFound();
    }

    // System enhancements

    public function test_a_system_enhancement_is_added_and_removed(): void
    {
        $release = GameRelease::factory()->create();
        $system = System::factory()->create(['name' => 'STE']);
        $enhancement = Enhancement::factory()->create(['name' => 'Sound']);

        $this->post(route('admin.games.releases.system-enhancement.store', [$release->game, $release]), [
            'system'      => $system->getKey(),
            'enhancement' => $enhancement->getKey(),
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $row = GameReleaseSystemEnhanced::sole();

        $this->assertSame($release->getKey(), $row->game_release_id);
        $this->assertSame('STE', $row->system->name);
        $this->assertSame('Sound', $row->enhancement->name);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);

        $this->delete(route('admin.games.releases.system-enhancement.destroy', [$release->game, $release, $row]))
            ->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $this->assertSame(0, GameReleaseSystemEnhanced::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    /**
     * "Enhanced for the STE" with no particular enhancement named is a valid
     * entry, so only the system is required.
     */
    public function test_a_system_enhancement_needs_only_a_known_system(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system-enhancement.store', [$release->game, $release]), [
            'enhancement' => Enhancement::factory()->create()->getKey(),
        ])->assertSessionHasErrors('system');

        $this->post(route('admin.games.releases.system-enhancement.store', [$release->game, $release]), [
            'system' => 9999,
        ])->assertSessionHasErrors('system');

        $this->assertSame(0, GameReleaseSystemEnhanced::query()->count());
        $this->assertNoChangelog();
    }

    public function test_removing_one_system_enhancement_leaves_the_other(): void
    {
        $release = GameRelease::factory()
            ->enhancedForSystem('STE')
            ->enhancedForSystem('Falcon')
            ->create();

        $doomed = $release->systemEnhanced->firstWhere('system.name', 'STE');

        $this->delete(route('admin.games.releases.system-enhancement.destroy', [$release->game, $release, $doomed]))
            ->assertRedirect();

        $this->assertSame(
            ['Falcon'],
            $release->fresh()->systemEnhanced->pluck('system.name')->all()
        );
    }

    // Incompatible TOS versions

    public function test_an_incompatible_tos_is_added_and_removed(): void
    {
        $release = GameRelease::factory()->create();
        $tos = Tos::factory()->create(['name' => '1.62']);
        $language = Language::factory()->create(['id' => 'de', 'name' => 'German']);

        $this->post(route('admin.games.releases.system-tos-incompatibility.store', [$release->game, $release]), [
            'tos'      => $tos->getKey(),
            'language' => $language->id,
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $row = GameReleaseTosVersionIncompatibility::sole();

        $this->assertSame($release->getKey(), $row->game_release_id);
        $this->assertSame('1.62', $row->tos->name);
        $this->assertSame('de', $row->language_id);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);

        $this->delete(route('admin.games.releases.system-tos-incompatibility.destroy', [$release->game, $release, $row]))
            ->assertRedirect();

        $this->assertSame(0, GameReleaseTosVersionIncompatibility::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_an_incompatible_tos_needs_a_known_version_and_language(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system-tos-incompatibility.store', [$release->game, $release]))
            ->assertSessionHasErrors('tos');

        $this->post(route('admin.games.releases.system-tos-incompatibility.store', [$release->game, $release]), [
            'tos'      => Tos::factory()->create()->getKey(),
            'language' => 'zz',
        ])->assertSessionHasErrors('language');

        $this->assertSame(0, GameReleaseTosVersionIncompatibility::query()->count());
        $this->assertNoChangelog();
    }

    public function test_removing_one_incompatible_tos_leaves_the_other(): void
    {
        $release = GameRelease::factory()
            ->incompatibleWithTos('1.00')
            ->incompatibleWithTos('1.62')
            ->create();

        $doomed = $release->tosIncompatibles->firstWhere('tos.name', '1.00');

        $this->delete(route('admin.games.releases.system-tos-incompatibility.destroy', [
            $release->game, $release, $doomed,
        ]))->assertRedirect();

        $this->assertSame(
            ['1.62'],
            $release->fresh()->tosIncompatibles->pluck('tos.name')->all()
        );
    }

    // Memory

    public function test_minimum_and_incompatible_memories_are_saved(): void
    {
        $release = GameRelease::factory()->create();
        $half = Memory::factory()->create(['name' => '512 KB']);
        $four = Memory::factory()->create(['name' => '4 MB']);

        $this->put(route('admin.games.releases.system-memory.update', [$release->game, $release]), [
            'minimum_memory'      => [$half->getKey()],
            'incompatible_memory' => [$four->getKey()],
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $release->refresh();

        $this->assertSame(['512 KB'], $release->memoryMinimums->pluck('name')->all());
        $this->assertSame(['4 MB'], $release->memoryIncompatibles->pluck('name')->all());

        // The two lists are logged separately, so assertChangelog() - which
        // insists on exactly one match - cannot be used here.
        $this->assertSame(1, Changelog::query()->where('sub_section', 'Minimum Memory')->count());
        $this->assertSame(1, Changelog::query()->where('sub_section', 'Incompatible Memory')->count());
    }

    public function test_saving_an_empty_memory_panel_detaches_both_lists(): void
    {
        $release = GameRelease::factory()
            ->requiringMemory('512 KB')
            ->incompatibleWithMemory('4 MB')
            ->create();

        $this->put(route('admin.games.releases.system-memory.update', [$release->game, $release]))
            ->assertRedirect();

        $release->refresh();

        $this->assertCount(0, $release->memoryMinimums);
        $this->assertCount(0, $release->memoryIncompatibles);
    }

    public function test_the_memory_lists_must_be_posted_as_lists(): void
    {
        $release = GameRelease::factory()->create();

        $this->put(route('admin.games.releases.system-memory.update', [$release->game, $release]), [
            'minimum_memory'      => Memory::factory()->create()->getKey(),
            'incompatible_memory' => Memory::factory()->create()->getKey(),
        ])->assertSessionHasErrors(['minimum_memory', 'incompatible_memory']);

        $this->assertCount(0, $release->fresh()->memoryMinimums);
        $this->assertNoChangelog();
    }

    // Memory enhancements

    public function test_a_memory_enhancement_is_added_and_removed(): void
    {
        $release = GameRelease::factory()->create();
        $memory = Memory::factory()->create(['name' => '1 MB']);
        $enhancement = Enhancement::factory()->create(['name' => 'Extra levels']);

        $this->post(route('admin.games.releases.system-memory-enhancement.store', [$release->game, $release]), [
            'memory'             => $memory->getKey(),
            'memory_enhancement' => $enhancement->getKey(),
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $row = GameReleaseMemoryEnhanced::sole();

        $this->assertSame($release->getKey(), $row->game_release_id);
        $this->assertSame('1 MB', $row->memory->name);
        $this->assertSame('Extra levels', $row->enhancement->name);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);

        $this->delete(route('admin.games.releases.system-memory-enhancement.destroy', [
            $release->game, $release, $row,
        ]))->assertRedirect();

        $this->assertSame(0, GameReleaseMemoryEnhanced::query()->count());
        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_a_memory_enhancement_needs_a_known_memory(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system-memory-enhancement.store', [$release->game, $release]))
            ->assertSessionHasErrors('memory');

        $this->post(route('admin.games.releases.system-memory-enhancement.store', [$release->game, $release]), [
            'memory' => 9999,
        ])->assertSessionHasErrors('memory');

        $this->assertSame(0, GameReleaseMemoryEnhanced::query()->count());
        $this->assertNoChangelog();
    }

    public function test_removing_one_memory_enhancement_leaves_the_other(): void
    {
        $release = GameRelease::factory()
            ->enhancedForMemory('1 MB')
            ->enhancedForMemory('4 MB')
            ->create();

        $doomed = $release->memoryEnhanced->firstWhere('memory.name', '1 MB');

        $this->delete(route('admin.games.releases.system-memory-enhancement.destroy', [
            $release->game, $release, $doomed,
        ]))->assertRedirect();

        $this->assertSame(
            ['4 MB'],
            $release->fresh()->memoryEnhanced->pluck('memory.name')->all()
        );
    }

    // Copy protection

    /**
     * The notes live on the pivot rather than on the protection, because the
     * same scheme is described differently from one release to the next.
     */
    public function test_a_copy_protection_is_added_with_its_notes_and_removed(): void
    {
        $release = GameRelease::factory()->create();
        $protection = CopyProtection::factory()->create(['name' => 'Code wheel']);

        $this->post(route('admin.games.releases.system-copy-protection.store', [$release->game, $release]), [
            'copy_protection'       => $protection->getKey(),
            'copy_protection_notes' => 'Page 12 of the manual',
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $attached = $release->fresh()->copyProtections;

        $this->assertSame(['Code wheel'], $attached->pluck('name')->all());
        $this->assertSame('Page 12 of the manual', $attached->first()->pivot->notes);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);

        $this->delete(route('admin.games.releases.system-copy-protection.destroy', [
            $release->game, $release, $protection,
        ]))->assertRedirect();

        $this->assertCount(0, $release->fresh()->copyProtections);
        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_a_copy_protection_must_be_a_known_one(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system-copy-protection.store', [$release->game, $release]))
            ->assertSessionHasErrors('copy_protection');

        $this->post(route('admin.games.releases.system-copy-protection.store', [$release->game, $release]), [
            'copy_protection' => 9999,
        ])->assertSessionHasErrors('copy_protection');

        $this->assertCount(0, $release->fresh()->copyProtections);
        $this->assertNoChangelog();
    }

    /**
     * Detaching goes through the protection rather than the pivot row, so a
     * release carrying two schemes has to lose only the one named.
     */
    public function test_removing_one_copy_protection_leaves_the_other(): void
    {
        $release = GameRelease::factory()
            ->copyProtectedBy('Code wheel')
            ->copyProtectedBy('Manual lookup')
            ->create();

        $doomed = $release->copyProtections->firstWhere('name', 'Code wheel');

        $this->delete(route('admin.games.releases.system-copy-protection.destroy', [
            $release->game, $release, $doomed,
        ]))->assertRedirect();

        $this->assertSame(['Manual lookup'], $release->fresh()->copyProtections->pluck('name')->all());
    }

    // Disk protection

    public function test_a_disk_protection_is_added_with_its_notes_and_removed(): void
    {
        $release = GameRelease::factory()->create();
        $protection = DiskProtection::factory()->create(['name' => 'Rob Northen Copylock']);

        $this->post(route('admin.games.releases.system-disk-protection.store', [$release->game, $release]), [
            'disk_protection'       => $protection->getKey(),
            'disk_protection_notes' => 'Track 79',
        ])->assertRedirect(route('admin.games.releases.system.index', [$release->game, $release]));

        $attached = $release->fresh()->diskProtections;

        $this->assertSame(['Rob Northen Copylock'], $attached->pluck('name')->all());
        $this->assertSame('Track 79', $attached->first()->pivot->notes);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);

        $this->delete(route('admin.games.releases.system-disk-protection.destroy', [
            $release->game, $release, $protection,
        ]))->assertRedirect();

        $this->assertCount(0, $release->fresh()->diskProtections);
        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_a_disk_protection_must_be_a_known_one(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.system-disk-protection.store', [$release->game, $release]))
            ->assertSessionHasErrors('disk_protection');

        $this->assertCount(0, $release->fresh()->diskProtections);
        $this->assertNoChangelog();
    }

    public function test_removing_one_disk_protection_leaves_the_other(): void
    {
        $release = GameRelease::factory()
            ->diskProtectedBy('Rob Northen Copylock')
            ->diskProtectedBy('Long track')
            ->create();

        $doomed = $release->diskProtections->firstWhere('name', 'Rob Northen Copylock');

        $this->delete(route('admin.games.releases.system-disk-protection.destroy', [
            $release->game, $release, $doomed,
        ]))->assertRedirect();

        $this->assertSame(['Long track'], $release->fresh()->diskProtections->pluck('name')->all());
    }

    /**
     * A protection detached from one release must stay attached to any other
     * release using it - the pivot is keyed on both.
     */
    public function test_detaching_a_protection_does_not_touch_other_releases(): void
    {
        $protection = CopyProtection::factory()->create(['name' => 'Code wheel']);

        $release = GameRelease::factory()->create();
        $other = GameRelease::factory()->create();

        $release->copyProtections()->attach($protection, ['notes' => null]);
        $other->copyProtections()->attach($protection, ['notes' => null]);

        $this->delete(route('admin.games.releases.system-copy-protection.destroy', [
            $release->game, $release, $protection,
        ]));

        $this->assertSame(1, DB::table('game_release_copy_protection')->count());
        $this->assertCount(1, $other->fresh()->copyProtections);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $release = GameRelease::factory()->create();

        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.system.index', [$release->game, $release])
        );
        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.system.update', [$release->game, $release]),
            'post'
        );
    }
}
