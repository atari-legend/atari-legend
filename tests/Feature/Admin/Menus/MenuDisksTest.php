<?php

namespace Tests\Feature\Admin\Menus;

use App\Models\Changelog;
use App\Models\Game;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuDiskDump;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\GameRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;
use ZipArchive;

/**
 * The two halves of a menu disk that the set and menu tests do not reach: the
 * dump - the disk image itself - and the contents, which say what is on it.
 *
 * A dump is always stored as a ZIP holding a single disk image named after the
 * disk, whatever the admin uploaded, so most of these tests are about what
 * comes out the other end rather than about what went in. The contents are
 * three-way: software, a game with no release, or a release, and deleting one
 * can take a release with it.
 */
class MenuDisksTest extends AdminTestCase
{
    private function disk(string $part = 'A', string $setName = 'Automation'): MenuDisk
    {
        $menu = Menu::factory()->create([
            'menu_set_id' => MenuSet::factory()->create(['name' => $setName])->getKey(),
            'number'      => 189,
            'version'     => null,
        ]);

        return MenuDisk::factory()->create([
            'menu_id' => $menu->getKey(),
            'part'    => $part,
        ]);
    }

    /**
     * A ZIP the admin might upload. Built for real rather than faked, because
     * the controller opens it and reads what is inside.
     */
    private function zipUpload(array $entries, string $name = 'dump.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test-zip') . '.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $entryName => $content) {
            $zip->addFromString($entryName, $content);
        }
        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }

    /**
     * @return array The names of the files inside a ZIP on the public disk
     */
    private function storedZipEntries(string $path): array
    {
        $zip = new ZipArchive();
        $zip->open(Storage::disk('public')->path($path));

        $names = [];
        for ($i = 0; $i < $zip->count(); $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();

        return $names;
    }

    /**
     * Adding content to a disk goes through the same store route the admin
     * posts to, so that the content is wired to its game, release or software
     * exactly as the panel would have wired it.
     */
    private function addContent(MenuDisk $disk, array $attributes): MenuDiskContent
    {
        $this->post(
            route('admin.menus.disks.content.store', $disk),
            array_merge(['disk' => $disk->getKey(), 'order' => 1], $attributes)
        )->assertRedirect();

        return MenuDiskContent::query()->latest('id')->first();
    }

    // Disk forms

    public function test_the_create_form_names_the_menu_the_disk_will_belong_to(): void
    {
        $disk = $this->disk();

        $this->get(route('admin.menus.disks.create', ['menu' => $disk->menu->getKey()]))
            ->assertOk()
            ->assertSee('Automation')
            ->assertSee('Create disk');
    }

    public function test_the_edit_form_shows_the_disk_and_its_dump(): void
    {
        $disk = $this->disk();
        $dump = MenuDiskDump::factory()->inFormat('MSA')->create(['user_id' => $this->admin->getKey()]);
        $disk->menuDiskDump()->associate($dump);
        $disk->save();

        $this->get(route('admin.menus.disks.edit', $disk))
            ->assertOk()
            ->assertSee('Automation')
            ->assertSee('MSA')
            ->assertSee($this->admin->userid);
    }

    // Dumps

    public function test_a_bare_disk_image_is_zipped_up_under_the_disk_name(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => UploadedFile::fake()->createWithContent('somedump.st', 'DISKIMAGE'),
        ])->assertRedirect(route('admin.menus.disks.edit', $disk));

        $dump = MenuDiskDump::sole();

        $this->assertSame('ST', $dump->format);
        $this->assertSame(9, $dump->size);
        $this->assertSame(hash('sha512', 'DISKIMAGE'), $dump->sha512);
        $this->assertSame($this->admin->getKey(), $dump->user_id);
        $this->assertSame($dump->getKey(), $disk->fresh()->menu_disk_dump_id);

        Storage::disk('public')->assertExists('zips/menus/' . $dump->getKey() . '.zip');
        $this->assertSame(
            ['Automation #189A.st'],
            $this->storedZipEntries('zips/menus/' . $dump->getKey() . '.zip')
        );

        $this->assertChangelog(Changelog::INSERT, 'Menu Disks', 'Automation #189A');
    }

    /**
     * An uploaded ZIP is unpacked and re-zipped: the checksum and size describe
     * the disk image inside it, not the archive, and the image is renamed after
     * the disk so that a download is self-describing.
     */
    public function test_a_zipped_disk_image_is_repacked_and_measured_from_its_contents(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => $this->zipUpload(['whatever-the-dumper-called-it.stx' => 'DISKIMAGE']),
        ])->assertRedirect(route('admin.menus.disks.edit', $disk));

        $dump = MenuDiskDump::sole();

        $this->assertSame('STX', $dump->format);
        $this->assertSame(9, $dump->size);
        $this->assertSame(hash('sha512', 'DISKIMAGE'), $dump->sha512);

        $this->assertSame(
            ['Automation #189A.stx'],
            $this->storedZipEntries('zips/menus/' . $dump->getKey() . '.zip')
        );
    }

    public function test_uploading_a_dump_again_replaces_the_one_already_there(): void
    {
        Storage::fake('public');

        $disk = $this->disk();
        $dump = MenuDiskDump::factory()->inFormat('MSA')->create(['user_id' => $this->admin->getKey()]);
        $disk->menuDiskDump()->associate($dump);
        $disk->save();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => UploadedFile::fake()->createWithContent('somedump.stx', 'BETTERDUMP'),
        ])->assertRedirect();

        $this->assertSame(1, MenuDiskDump::query()->count());

        $dump->refresh();

        $this->assertSame('STX', $dump->format);
        $this->assertSame(hash('sha512', 'BETTERDUMP'), $dump->sha512);
        $this->assertSame($dump->getKey(), $disk->fresh()->menu_disk_dump_id);

        Storage::disk('public')->assertExists('zips/menus/' . $dump->getKey() . '.zip');
        $this->assertChangelog(Changelog::UPDATE, 'Menu Disks', 'Automation #189A');
    }

    public function test_a_file_that_is_not_a_disk_image_is_refused(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => UploadedFile::fake()->createWithContent('notes.txt', 'not a disk'),
        ])
            ->assertRedirect(route('admin.menus.disks.edit', $disk))
            ->assertSessionHas('alert-danger', 'Unsupported file extension: TXT');

        $this->assertSame(0, MenuDiskDump::query()->count());
        $this->assertNoChangelog();
    }

    /**
     * A ZIP is only a wrapper around one disk image. Several files, or a file
     * that is not a disk image, mean the wrong archive was picked - accepting
     * either would put something undownloadable on the disk.
     */
    public function test_a_zip_must_hold_exactly_one_disk_image(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => $this->zipUpload(['a.st' => 'ONE', 'b.st' => 'TWO']),
        ])->assertSessionHas('alert-danger', 'More than one file in the ZIP archive. Please only include a single disk image.');

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => $this->zipUpload(['readme.txt' => 'not a disk']),
        ])->assertSessionHas('alert-danger');

        $this->assertSame(0, MenuDiskDump::query()->count());
        $this->assertNull($disk->fresh()->menu_disk_dump_id);
        $this->assertNoChangelog();
    }

    public function test_a_dump_can_be_removed_from_a_disk(): void
    {
        Storage::fake('public');

        $disk = $this->disk();

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => UploadedFile::fake()->createWithContent('somedump.st', 'DISKIMAGE'),
        ])->assertRedirect();

        $dump = MenuDiskDump::sole();

        $this->delete(route('admin.menus.disks.destroyDump', [$disk, $dump]))
            ->assertRedirect(route('admin.menus.disks.edit', $disk));

        $this->assertSame(0, MenuDiskDump::query()->count());
        $this->assertNull($disk->fresh()->menu_disk_dump_id);
        Storage::disk('public')->assertMissing('zips/menus/' . $dump->getKey() . '.zip');

        $this->assertChangelog(Changelog::DELETE, 'Menu Disks', 'Automation #189A');
    }

    /**
     * The dump belongs to one disk only, so a delete aimed at another disk must
     * do nothing rather than unhook it from its owner.
     */
    public function test_a_dump_is_not_removed_through_a_different_disk(): void
    {
        Storage::fake('public');

        $disk = $this->disk();
        $otherDisk = $this->disk('B', 'Superior');

        $this->post(route('admin.menus.disks.storeDump', $disk), [
            'dump' => UploadedFile::fake()->createWithContent('somedump.st', 'DISKIMAGE'),
        ])->assertRedirect();

        $dump = MenuDiskDump::sole();

        $this->delete(route('admin.menus.disks.destroyDump', [$otherDisk, $dump]))->assertRedirect();

        $this->assertSame(1, MenuDiskDump::query()->count());
        $this->assertSame($dump->getKey(), $disk->fresh()->menu_disk_dump_id);
        Storage::disk('public')->assertExists('zips/menus/' . $dump->getKey() . '.zip');
    }

    // Contents

    public function test_the_content_create_form_renders_for_each_kind_of_content(): void
    {
        $disk = $this->disk();

        $this->get(route('admin.menus.disks.content.create', ['disk' => $disk, 'type' => 'software']))
            ->assertOk()
            ->assertSee('Type a software name...');

        $this->get(route('admin.menus.disks.content.create', ['disk' => $disk, 'type' => 'game']))
            ->assertOk()
            ->assertSee('Type a game name...');

        $this->get(route('admin.menus.disks.content.create', ['disk' => $disk, 'type' => 'release']))
            ->assertOk()
            ->assertSee('Create a new release of a game');
    }

    /**
     * The release form offers the releases already on the menu, so that a doc
     * or a trainer can be attached to a game sitting on another disk of the
     * same menu.
     */
    public function test_the_release_form_offers_the_releases_already_on_the_menu(): void
    {
        $diskA = $this->disk();
        $diskB = MenuDisk::factory()->create(['menu_id' => $diskA->menu->getKey(), 'part' => 'B']);

        $this->addContent($diskA, [
            'type'   => 'release',
            'action' => 'create-release',
            'game'   => Game::factory()->named('Xenon')->create()->getKey(),
        ]);

        $this->get(route('admin.menus.disks.content.create', ['disk' => $diskB, 'type' => 'release']))
            ->assertOk()
            ->assertSee('Xenon');
    }

    public function test_a_game_put_on_a_disk_gets_an_unofficial_release_of_its_own(): void
    {
        $disk = $this->disk();
        $game = Game::factory()->named('Xenon')->create();

        $content = $this->addContent($disk, [
            'type'   => 'release',
            'action' => 'create-release',
            'game'   => $game->getKey(),
        ]);

        $release = GameRelease::sole();

        $this->assertSame($game->getKey(), $release->game_id);
        $this->assertSame(GameRelease::TYPE_UNOFFICIAL, $release->type);
        $this->assertSame($release->getKey(), $content->game_release_id);
        $this->assertSame($disk->getKey(), $content->menu_disk_id);

        $this->assertChangelog(Changelog::INSERT, 'Menu Disks', 'A');
    }

    public function test_the_content_edit_form_names_what_the_content_points_at(): void
    {
        $disk = $this->disk();
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $content = $this->addContent($disk, ['type' => 'software', 'software' => $software->getKey()]);

        $this->get(route('admin.menus.disks.content.edit', ['disk' => $disk, 'content' => $content]))
            ->assertOk()
            ->assertSee('Xtracker');
    }

    public function test_content_can_be_edited(): void
    {
        $disk = $this->disk();
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $content = $this->addContent($disk, ['type' => 'software', 'software' => $software->getKey()]);

        $this->put(route('admin.menus.disks.content.update', ['disk' => $disk, 'content' => $content]), [
            'order'        => 3,
            'version'      => '2.3',
            'requirements' => 'TOS 1.62',
        ])->assertRedirect(route('admin.menus.disks.edit', $disk));

        $content->refresh();

        $this->assertSame(3, $content->order);
        $this->assertSame('2.3', $content->version);
        $this->assertSame('TOS 1.62', $content->requirements);
        $this->assertSame($software->getKey(), $content->menu_software_id);

        $this->assertChangelog(Changelog::UPDATE, 'Menu Disks', 'A');
    }

    /**
     * Content pointing straight at a game rather than at a release is by
     * definition not the game itself, so it has to say what it is - a doc, a
     * trainer, a hint.
     */
    public function test_content_needs_an_order_and_a_subtype_when_it_points_at_a_game(): void
    {
        $disk = $this->disk();
        $game = Game::factory()->named('Xenon')->create();

        $content = $this->addContent($disk, [
            'type'    => 'game',
            'game'    => $game->getKey(),
            'subtype' => 'doc',
        ]);

        $this->put(route('admin.menus.disks.content.update', ['disk' => $disk, 'content' => $content]), [
            'order' => 'first',
        ])->assertSessionHasErrors('order');

        $this->put(route('admin.menus.disks.content.update', ['disk' => $disk, 'content' => $content]), [
            'order'   => 2,
            'subtype' => '',
        ])->assertSessionHasErrors('subtype');

        $content->refresh();

        $this->assertSame(1, $content->order);
        $this->assertSame('doc', $content->subtype);
    }

    /**
     * A release created for a menu exists only to be on that menu, so removing
     * the game from the disk removes the release too - and with it the docs and
     * trainers hanging off that release, which would otherwise be orphaned.
     */
    public function test_removing_a_game_from_a_disk_removes_its_release_and_extras(): void
    {
        $disk = $this->disk();
        $game = Game::factory()->named('Xenon')->create();

        $gameContent = $this->addContent($disk, [
            'type'   => 'release',
            'action' => 'create-release',
            'game'   => $game->getKey(),
        ]);

        $release = GameRelease::sole();

        $docContent = $this->addContent($disk, [
            'type'    => 'release',
            'action'  => 'use-release',
            'order'   => 2,
            'release' => $release->getKey(),
            'subtype' => 'doc',
        ]);

        $this->delete(route('admin.menus.disks.content.destroy', ['disk' => $disk, 'content' => $gameContent]))
            ->assertRedirect(route('admin.menus.disks.edit', $disk));

        $this->assertSame(0, MenuDiskContent::query()->count());
        $this->assertSame(0, GameRelease::query()->count());
        $this->assertNull(MenuDiskContent::find($docContent->getKey()));
        $this->assertSame(1, Game::query()->count());

        $this->assertChangelog(Changelog::DELETE, 'Menu Disks', 'A');
    }

    /**
     * Software is shared between menus, unlike the releases created for one, so
     * taking it off a disk must leave the software itself alone.
     */
    public function test_removing_software_from_a_disk_leaves_the_software_alone(): void
    {
        $disk = $this->disk();
        $software = MenuSoftware::factory()->named('Xtracker')->create();

        $content = $this->addContent($disk, ['type' => 'software', 'software' => $software->getKey()]);

        $this->delete(route('admin.menus.disks.content.destroy', ['disk' => $disk, 'content' => $content]))
            ->assertRedirect();

        $this->assertSame(0, MenuDiskContent::query()->count());
        $this->assertSame(1, MenuSoftware::query()->count());
    }

    /**
     * Deleting a whole disk has to do what deleting each content would have
     * done, releases included.
     */
    public function test_deleting_a_disk_takes_its_contents_and_their_releases(): void
    {
        $disk = $this->disk();
        $game = Game::factory()->named('Xenon')->create();

        $this->addContent($disk, [
            'type'   => 'release',
            'action' => 'create-release',
            'game'   => $game->getKey(),
        ]);
        $this->addContent($disk, [
            'type'    => 'game',
            'order'   => 2,
            'game'    => $game->getKey(),
            'subtype' => 'hints',
        ]);

        $this->delete(route('admin.menus.disks.destroy', $disk))
            ->assertRedirect(route('admin.menus.menus.edit', $disk->menu));

        $this->assertSame(0, MenuDisk::query()->count());
        $this->assertSame(0, MenuDiskContent::query()->count());
        $this->assertSame(0, GameRelease::query()->count());
        $this->assertSame(1, Game::query()->count());

        $this->assertChangelog(Changelog::DELETE, 'Menu Disks', 'Automation #189A');
    }

    public function test_disks_and_their_contents_are_closed_to_non_admins(): void
    {
        $disk = $this->disk();

        $this->assertNonAdminIsTurnedAway(route('admin.menus.disks.edit', $disk));
        $this->assertNonAdminIsTurnedAway(route('admin.menus.disks.content.create', ['disk' => $disk, 'type' => 'game']));
    }
}
