<?php

namespace Tests\Feature\Admin\Games\Releases;

use App\Models\Changelog;
use App\Models\Dump;
use App\Models\GameRelease;
use App\Models\Media;
use App\Models\MediaScan;
use App\Models\MediaScanType;
use App\Models\MediaType;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;
use ZipArchive;

/**
 * The media panel of a release, and everything hanging off it: the pieces of
 * media themselves, the disk images dumped from them, and the scans of their
 * labels.
 *
 * This is the deepest part of the data model - game, release, media, dump - and
 * the only part of the admin that writes two files per row, so the tests follow
 * the file as well as the row.
 */
class ReleaseMediaTest extends AdminTestCase
{
    use InteractsWithFilepond;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function media(?GameRelease $release = null): Media
    {
        return Media::factory()->create([
            'game_release_id' => ($release ?? GameRelease::factory()->create())->getKey(),
        ]);
    }

    /**
     * A dump is recognised by its header, falling back to the file extension.
     * 'RSY\0' is the STX magic; anything else lands on the extension.
     */
    private function stxContents(): string
    {
        return "RSY\x00" . str_repeat('x', 32);
    }

    // Media

    public function test_the_media_panel_loads(): void
    {
        $release = GameRelease::factory()->create();
        $media = Media::factory()->create([
            'game_release_id' => $release->getKey(),
            'label'           => 'Disk A',
        ]);
        Dump::factory()->create(['media_id' => $media->getKey(), 'format' => 'STX']);

        $this->get(route('admin.games.releases.medias.index', [$release->game, $release]))
            ->assertOk()
            ->assertSee('Disk A')
            ->assertSee('STX');
    }

    /**
     * The Add button takes no input at all: a new media is assumed to be a
     * floppy, which is what all but a handful of ST releases came on.
     */
    public function test_a_new_media_defaults_to_a_floppy(): void
    {
        $release = GameRelease::factory()->create();
        MediaType::factory()->create(['name' => 'Cartridge']);
        $floppy = MediaType::factory()->create(['name' => '3.5" DD floppy disk']);

        $this->post(route('admin.games.releases.medias.store', [$release->game, $release]))
            ->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $media = Media::sole();

        $this->assertSame($release->getKey(), $media->game_release_id);
        $this->assertSame($floppy->getKey(), $media->media_type_id);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);
    }

    public function test_a_medias_type_and_label_can_be_changed(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $cartridge = MediaType::factory()->create(['name' => 'Cartridge']);

        $this->put(route('admin.games.releases.medias.update', [$release->game, $release, $media]), [
            'type'  => $cartridge->getKey(),
            'label' => 'Disk B',
        ])->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $media->refresh();

        $this->assertSame($cartridge->getKey(), $media->media_type_id);
        $this->assertSame('Disk B', $media->label);
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->game_name);
    }

    /**
     * The type dropdown has a blank first entry, which has to clear the type
     * rather than be rejected.
     */
    public function test_a_media_can_have_no_type(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->put(route('admin.games.releases.medias.update', [$release->game, $release, $media]), [
            'type'  => '',
            'label' => 'Disk A',
        ])->assertRedirect();

        $this->assertNull($media->fresh()->media_type_id);
    }

    public function test_an_unknown_media_type_is_a_404(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->put(route('admin.games.releases.medias.update', [$release->game, $release, $media]), [
            'type' => 9999,
        ])->assertNotFound();
    }

    /**
     * Deleting is the danger button on the same form as the save, so it arrives
     * as a PUT carrying a 'delete' flag rather than as a DELETE.
     */
    public function test_the_delete_button_removes_the_media_and_its_files(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $survivor = $this->media($release);

        $dump = Dump::factory()->create(['media_id' => $media->getKey()]);
        $scan = MediaScan::factory()->create(['media_id' => $media->getKey()]);

        Storage::disk('public')->put($dump->path, 'zip');
        Storage::disk('public')->put($scan->path, 'image');

        $this->put(route('admin.games.releases.medias.update', [$release->game, $release, $media]), [
            'delete' => 'delete',
        ])->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $this->assertSame([$survivor->getKey()], Media::query()->pluck('id')->all());

        Storage::disk('public')->assertMissing($dump->path);
        Storage::disk('public')->assertMissing($scan->path);

        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_the_delete_route_is_not_allowed(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->delete(route('admin.games.releases.medias.update', [$release->game, $release, $media]))
            ->assertStatus(405);
    }

    // Dumps

    public function test_a_dump_is_uploaded_and_zipped(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.dumps.store', [$release->game, $release, $media]), [
            'file' => [$this->filepondServerId('xenon.stx', $this->stxContents())],
        ])->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $dump = Dump::sole();

        $this->assertSame($media->getKey(), $dump->media_id);
        $this->assertSame('STX', $dump->format);
        $this->assertSame(hash('sha512', $this->stxContents()), $dump->sha512);
        $this->assertSame(strlen($this->stxContents()), $dump->size);
        $this->assertSame($this->admin->getKey(), $dump->user_id);

        Storage::disk('public')->assertExists($dump->path);
        Storage::assertMissing('filepond/xenon.stx');

        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);
    }

    /**
     * With no header to go on the extension decides the format, which is how
     * plain .st images - the commonest kind - get in.
     */
    public function test_an_unrecognised_header_falls_back_to_the_extension(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.dumps.store', [$release->game, $release, $media]), [
            'file' => [$this->filepondServerId('xenon.st', str_repeat('x', 32))],
        ])->assertRedirect();

        $this->assertSame('ST', Dump::sole()->format);
    }

    /**
     * Anything that is not one of the supported disk image formats is dropped
     * silently - the panel has no validation to report it with.
     */
    public function test_a_file_that_is_not_a_dump_is_ignored(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.dumps.store', [$release->game, $release, $media]), [
            'file' => [$this->filepondServerId('readme.txt', str_repeat('x', 32))],
        ])->assertRedirect();

        $this->assertSame(0, Dump::query()->count());
        $this->assertNoChangelog();
    }

    /**
     * A multi-disk game is usually uploaded as one ZIP, which is unpacked and
     * every disk image inside it stored as a dump of its own.
     */
    public function test_a_zip_is_unpacked_into_one_dump_per_image(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.dumps.store', [$release->game, $release, $media]), [
            'file' => [$this->zipServerId([
                'disk1.st'   => str_repeat('a', 32),
                'disk2.st'   => str_repeat('b', 32),
                'readme.txt' => 'Notes',
            ])],
        ])->assertRedirect();

        $this->assertSame(['ST', 'ST'], Dump::query()->pluck('format')->all());

        foreach (Dump::all() as $dump) {
            Storage::disk('public')->assertExists($dump->path);
        }
    }

    public function test_an_empty_upload_slot_is_skipped(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.dumps.store', [$release->game, $release, $media]), [
            'file' => [null],
        ])->assertRedirect();

        $this->assertSame(0, Dump::query()->count());
    }

    public function test_a_dumps_notes_can_be_edited(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $dump = Dump::factory()->create(['media_id' => $media->getKey()]);

        $this->put(route('admin.games.releases.medias.dumps.update', [
            $release->game, $release, $media, $dump,
        ]), ['info' => 'Cracked by The Replicants.'])
            ->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $this->assertSame('Cracked by The Replicants.', $dump->fresh()->info);
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->game_name);
    }

    public function test_a_dump_is_deleted_with_its_zip(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $dump = Dump::factory()->create(['media_id' => $media->getKey()]);
        $survivor = Dump::factory()->create(['media_id' => $media->getKey()]);

        Storage::disk('public')->put($dump->path, 'zip');
        Storage::disk('public')->put($survivor->path, 'zip');

        $this->delete(route('admin.games.releases.medias.dumps.destroy', [
            $release->game, $release, $media, $dump,
        ]))->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $this->assertSame([$survivor->getKey()], Dump::query()->pluck('id')->all());

        Storage::disk('public')->assertMissing($dump->path);
        Storage::disk('public')->assertExists($survivor->path);

        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    // Media scans

    public function test_a_media_scan_is_uploaded(): void
    {
        MediaScanType::factory()->create();

        $release = GameRelease::factory()->create();
        $media = $this->media($release);

        $this->post(route('admin.games.releases.medias.scans.store', [$release->game, $release, $media]), [
            'file' => [$this->filepondServerId('label.png', 'image')],
        ])->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $scan = MediaScan::sole();

        $this->assertSame($media->getKey(), $scan->media_id);
        $this->assertSame('png', $scan->imgext);
        $this->assertSame(MediaScanType::TYPE_OTHER, $scan->type->name);

        Storage::disk('public')->assertExists($scan->path);
        Storage::assertMissing('filepond/label.png');

        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->game_name);
    }

    /**
     * Uploads always land as 'Other', because nothing in the file says which
     * side of which disk it is - the type is picked afterwards.
     */
    public function test_a_media_scans_type_can_be_corrected(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $scan = MediaScan::factory()->create(['media_id' => $media->getKey()]);
        $label = MediaScanType::factory()->named('Disk label')->create();

        $this->put(route('admin.games.releases.medias.scans.update', [
            $release->game, $release, $media, $scan,
        ]), ['type' => $label->getKey()])
            ->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $this->assertSame('Disk label', $scan->fresh()->type->name);
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->game_name);
    }

    public function test_an_unknown_media_scan_type_is_a_404(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $scan = MediaScan::factory()->create(['media_id' => $media->getKey()]);

        $this->put(route('admin.games.releases.medias.scans.update', [
            $release->game, $release, $media, $scan,
        ]), ['type' => 9999])->assertNotFound();
    }

    public function test_a_media_scan_is_deleted_with_its_image(): void
    {
        $release = GameRelease::factory()->create();
        $media = $this->media($release);
        $scan = MediaScan::factory()->create(['media_id' => $media->getKey()]);
        $survivor = MediaScan::factory()->create(['media_id' => $media->getKey()]);

        Storage::disk('public')->put($scan->path, 'image');
        Storage::disk('public')->put($survivor->path, 'image');

        $this->delete(route('admin.games.releases.medias.scans.destroy', [
            $release->game, $release, $media, $scan,
        ]))->assertRedirect(route('admin.games.releases.medias.index', [$release->game, $release]));

        $this->assertSame([$survivor->getKey()], MediaScan::query()->pluck('id')->all());

        Storage::disk('public')->assertMissing($scan->path);
        Storage::disk('public')->assertExists($survivor->path);

        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->game_name);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $release = GameRelease::factory()->create();

        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.medias.index', [$release->game, $release])
        );
        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.medias.store', [$release->game, $release]),
            'post'
        );
    }

    /**
     * Stage a ZIP of dumps the way a finished FilePond upload leaves it.
     *
     * @param  array<string, string>  $files  Filename inside the ZIP => contents
     */
    private function zipServerId(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dumps') . '.zip';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        $serverId = $this->filepondServerId('dumps.zip', file_get_contents($path));
        unlink($path);

        return $serverId;
    }
}
