<?php

namespace Tests\Feature\Admin\Games\Releases;

use App\Models\Changelog;
use App\Models\GameRelease;
use App\Models\GameReleaseScan;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Admin\AdminTestCase;

/**
 * The scans panel of a release: the box front and back, and whatever else came
 * in the package.
 *
 * Uploading is the only way to create a scan, and the type is not asked for -
 * it is guessed from the filename and corrected afterwards - so the store tests
 * are as much about that guess as about the file landing on disk.
 */
class ReleaseScansTest extends AdminTestCase
{
    use InteractsWithFilepond;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_the_scans_panel_loads(): void
    {
        $release = GameRelease::factory()->create();
        GameReleaseScan::factory()->create(['game_release_id' => $release->getKey()]);

        $this->get(route('admin.games.releases.scans.index', [$release->game, $release]))
            ->assertOk()
            ->assertSee(GameReleaseScan::TYPE_BOX_FRONT);
    }

    public function test_a_scan_is_uploaded_and_stored_against_the_release(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.scans.store', [$release->game, $release]), [
            'file' => [$this->filepondServerId('goodie.png', 'image')],
        ])->assertRedirect(route('admin.games.releases.scans.index', [$release->game, $release]));

        $scan = GameReleaseScan::sole();

        $this->assertSame($release->getKey(), $scan->game_release_id);
        $this->assertSame('png', $scan->imgext);
        $this->assertSame(GameReleaseScan::TYPE_OTHER, $scan->type);

        Storage::disk('public')->assertExists($scan->path);
        $this->assertChangelog(Changelog::INSERT, 'Game Release', $release->game->name);
    }

    /**
     * Whoever scans a box names the files 'front' and 'back', so the panel
     * saves a step by reading the type off the filename.
     */
    public function test_the_type_is_guessed_from_the_filename(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.scans.store', [$release->game, $release]), [
            'file' => [
                $this->filepondServerId('Xenon-Front.png', 'image'),
                $this->filepondServerId('xenon back cover.png', 'image'),
                $this->filepondServerId('poster.png', 'image'),
            ],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [GameReleaseScan::TYPE_BOX_FRONT, GameReleaseScan::TYPE_BOX_BACK, GameReleaseScan::TYPE_OTHER],
            GameReleaseScan::query()->pluck('type')->all()
        );
    }

    /**
     * The temporary file FilePond left behind is the only copy, so it has to be
     * cleaned up once the scan has been written to its final home.
     */
    public function test_the_temporary_upload_is_removed(): void
    {
        $release = GameRelease::factory()->create();
        $serverId = $this->filepondServerId('front.png', 'image');

        $this->post(route('admin.games.releases.scans.store', [$release->game, $release]), [
            'file' => [$serverId],
        ])->assertRedirect();

        Storage::assertMissing('filepond/front.png');
    }

    /**
     * FilePond posts an empty slot for a file the user removed before
     * submitting, and that slot must not turn into an empty scan.
     */
    public function test_an_empty_upload_slot_is_skipped(): void
    {
        $release = GameRelease::factory()->create();

        $this->post(route('admin.games.releases.scans.store', [$release->game, $release]), [
            'file' => [null],
        ])->assertRedirect();

        $this->assertSame(0, GameReleaseScan::query()->count());
        $this->assertNoChangelog();
    }

    public function test_a_scans_type_and_notes_can_be_corrected(): void
    {
        $release = GameRelease::factory()->create();
        $scan = GameReleaseScan::factory()->ofType(GameReleaseScan::TYPE_OTHER)
            ->create(['game_release_id' => $release->getKey()]);

        $this->put(route('admin.games.releases.scans.update', [$release->game, $release, $scan]), [
            'type'  => GameReleaseScan::TYPE_BOX_BACK,
            'notes' => 'Scanned from the budget box.',
        ])->assertRedirect(route('admin.games.releases.scans.index', [$release->game, $release]));

        $scan->refresh();

        $this->assertSame(GameReleaseScan::TYPE_BOX_BACK, $scan->type);
        $this->assertSame('Scanned from the budget box.', $scan->notes);
        $this->assertChangelog(Changelog::UPDATE, 'Game Release', $release->game->name);
    }

    public function test_a_scan_is_deleted_with_its_image(): void
    {
        $release = GameRelease::factory()->create();
        $scan = GameReleaseScan::factory()->create(['game_release_id' => $release->getKey()]);
        $other = GameReleaseScan::factory()->ofType(GameReleaseScan::TYPE_BOX_BACK)
            ->create(['game_release_id' => $release->getKey()]);

        Storage::disk('public')->put($scan->path, 'image');
        Storage::disk('public')->put($other->path, 'image');

        $this->delete(route('admin.games.releases.scans.destroy', [$release->game, $release, $scan]))
            ->assertRedirect(route('admin.games.releases.scans.index', [$release->game, $release]));

        $this->assertSame([$other->getKey()], GameReleaseScan::query()->pluck('id')->all());

        Storage::disk('public')->assertMissing($scan->path);
        Storage::disk('public')->assertExists($other->path);

        $this->assertChangelog(Changelog::DELETE, 'Game Release', $release->game->name);
    }

    public function test_non_admins_are_turned_away(): void
    {
        $release = GameRelease::factory()->create();

        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.scans.index', [$release->game, $release])
        );
        $this->assertNonAdminIsTurnedAway(
            route('admin.games.releases.scans.store', [$release->game, $release]),
            'post'
        );
    }
}
