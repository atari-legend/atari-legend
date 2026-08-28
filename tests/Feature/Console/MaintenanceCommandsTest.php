<?php

namespace Tests\Feature\Console;

use App\Models\Dump;
use App\Models\MenuDisk;
use App\Models\MenuDiskDump;
use App\Models\User;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The housekeeping commands run from cron.
 *
 * Three of them take a --delete flag and report without it, which is the part
 * worth pinning: a dry run that quietly deletes, or a real run that quietly
 * does not, would both look the same in the output.
 */
class MaintenanceCommandsTest extends TestCase
{
    use RefreshDatabase;

    // user:delete-unverified

    private function unverifiedUser(string $name, string $joined): User
    {
        return User::factory()->unverified()->create([
            'userid'    => $name,
            'join_date' => (string) Carbon::parse($joined)->timestamp,
        ]);
    }

    public function test_unverified_accounts_are_only_reported_without_the_delete_flag(): void
    {
        $this->unverifiedUser('Bot', '2020-01-01');

        $this->artisan('user:delete-unverified')
            ->expectsOutputToContain('Deleting 1 unverified accounts')
            ->expectsOutputToContain('Bot')
            ->assertExitCode(0);

        $this->assertSame(1, User::where('userid', 'Bot')->count());
    }

    public function test_the_delete_flag_removes_them(): void
    {
        $this->unverifiedUser('Bot', '2020-01-01');

        $this->artisan('user:delete-unverified', ['--delete' => true])->assertExitCode(0);

        $this->assertSame(0, User::where('userid', 'Bot')->count());
    }

    /**
     * The cut-off is a day old, so an account that registered minutes ago is
     * still waiting for its verification e-mail rather than abandoned.
     */
    public function test_recent_and_verified_accounts_are_left_alone(): void
    {
        $this->unverifiedUser('JustRegistered', Carbon::now()->subMinutes(5)->toDateTimeString());
        User::factory()->create([
            'userid'    => 'Verified',
            'join_date' => (string) Carbon::parse('2020-01-01')->timestamp,
        ]);

        $this->artisan('user:delete-unverified', ['--delete' => true])->assertExitCode(0);

        $this->assertSame(1, User::where('userid', 'JustRegistered')->count());
        $this->assertSame(1, User::where('userid', 'Verified')->count());
    }

    /**
     * Three of the foreign keys pointing at `users` are ON DELETE RESTRICT, and
     * a delete that hits one used to throw out of `$users->each()` and abandon
     * the rest of an unattended run. The blocked account is now named and
     * stepped over, and the accounts behind it still go - which is why it is
     * the older of the two here: the command works through them by join date.
     */
    public function test_an_account_that_cannot_be_deleted_is_skipped_and_the_run_goes_on(): void
    {
        $blocked = $this->unverifiedUser('Uploader', '2020-01-01');
        Dump::factory()->create(['user_id' => $blocked->getKey()]);

        $this->unverifiedUser('Bot', '2020-06-01');

        $this->artisan('user:delete-unverified', ['--delete' => true])
            ->expectsOutputToContain(
                "Skipping 'Uploader' " . $blocked->email . ': still holds a game submission or a dump'
            )
            ->assertExitCode(0);

        $this->assertSame(1, User::where('userid', 'Uploader')->count());
        $this->assertSame(0, User::where('userid', 'Bot')->count());
    }

    public function test_nothing_to_delete_says_nothing(): void
    {
        $this->artisan('user:delete-unverified')
            ->doesntExpectOutputToContain('Deleting')
            ->assertExitCode(0);
    }

    // menus:check-dumps

    private function menuDump(): MenuDiskDump
    {
        $dump = MenuDiskDump::create(['format' => 'STX']);

        MenuDisk::factory()->create([
            'part'              => 'A',
            'menu_disk_dump_id' => $dump->getKey(),
        ]);

        return $dump;
    }

    public function test_check_dumps_passes_when_every_file_is_present(): void
    {
        Storage::fake('public');

        $dump = $this->menuDump();
        Storage::disk('public')->put('zips/menus/' . $dump->getKey() . '.zip', 'a zip');

        $this->artisan('menus:check-dumps')
            ->expectsOutputToContain('1/1 files present')
            ->expectsOutputToContain('All menu dump files are present.')
            ->assertExitCode(0);
    }

    /**
     * A missing file is a failure exit code, so cron can notice.
     */
    public function test_check_dumps_fails_and_names_the_missing_file(): void
    {
        Storage::fake('public');

        $dump = $this->menuDump();

        $this->artisan('menus:check-dumps')
            ->expectsOutputToContain('Missing: zips/menus/' . $dump->getKey() . '.zip')
            ->expectsOutputToContain('0/1 files present')
            ->expectsOutputToContain('1 file(s) missing')
            ->assertExitCode(1);
    }

    public function test_check_dumps_is_happy_with_nothing_to_check(): void
    {
        Storage::fake('public');

        $this->artisan('menus:check-dumps')
            ->expectsOutputToContain('0/0 files present')
            ->assertExitCode(0);
    }

    // links:check

    public function test_a_reachable_link_is_marked_active(): void
    {
        Http::fake(['*' => Http::response('Hello', 200)]);

        $website = Website::factory()->inactive()->create([
            'website_name' => 'Hatari',
            'website_url'  => 'https://hatari.example.org',
        ]);

        $this->artisan('links:check')
            ->expectsOutputToContain('Checking 1/1: Hatari')
            ->assertExitCode(0);

        $this->assertFalse((bool) $website->fresh()->inactive);
    }

    public function test_a_link_returning_an_error_is_marked_inactive(): void
    {
        Http::fake(['*' => Http::response('Gone', 404)]);

        $website = Website::factory()->create(['website_name' => 'Dead']);

        $this->artisan('links:check')
            ->expectsOutputToContain('Error: 404')
            ->assertExitCode(0);

        $this->assertTrue((bool) $website->fresh()->inactive);
    }

    /**
     * A host that does not answer at all raises rather than returning a
     * response, and must not take the whole run down with it.
     */
    public function test_a_link_that_cannot_be_reached_is_marked_inactive(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection timed out'));

        $reachable = Website::factory()->create(['website_name' => 'Unreachable']);

        $this->artisan('links:check')
            ->expectsOutputToContain('Connection timed out')
            ->assertExitCode(0);

        $this->assertTrue((bool) $reachable->fresh()->inactive);
    }

    // filepond:discard

    private function filepondDirectory(string $name, string $lastModified): string
    {
        $path = config('filepond.temporary_files_path') . '/' . $name;

        Storage::disk('local')->put($path . '/file.tmp', 'upload');
        touch(Storage::disk('local')->path($path), Carbon::parse($lastModified)->timestamp);

        return $path;
    }

    public function test_expired_uploads_are_only_reported_without_the_delete_flag(): void
    {
        Storage::fake('local');

        $old = $this->filepondDirectory('old', '2020-01-01');

        // The command reports at -v only, so a plain run is silent
        $this->artisan('filepond:discard', ['-v' => true])
            ->expectsOutputToContain('would have been deleted')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists($old . '/file.tmp');
    }

    public function test_the_delete_flag_discards_expired_uploads(): void
    {
        Storage::fake('local');

        $old = $this->filepondDirectory('old', '2020-01-01');
        $fresh = $this->filepondDirectory('fresh', Carbon::now()->toDateTimeString());

        $this->artisan('filepond:discard', ['--delete' => true])->assertExitCode(0);

        Storage::disk('local')->assertMissing($old . '/file.tmp');
        Storage::disk('local')->assertExists($fresh . '/file.tmp');
    }

    /**
     * The chunks directory belongs to uploads in progress, so it is never one
     * of the expired folders.
     */
    public function test_the_chunks_directory_is_never_discarded(): void
    {
        Storage::fake('local');

        $chunks = config('filepond.temporary_files_path') . '/chunks';
        Storage::disk('local')->put($chunks . '/part.tmp', 'chunk');
        touch(Storage::disk('local')->path($chunks), Carbon::parse('2020-01-01')->timestamp);

        $this->artisan('filepond:discard', ['--delete' => true])->assertExitCode(0);

        Storage::disk('local')->assertExists($chunks . '/part.tmp');
    }

    public function test_the_expiry_can_be_widened(): void
    {
        Storage::fake('local');

        $recent = $this->filepondDirectory('recent', Carbon::now()->subDays(3)->toDateTimeString());

        // Three days old, so a 7 day expiry leaves it alone
        $this->artisan('filepond:discard', ['--expiry' => 7, '--delete' => true])->assertExitCode(0);
        Storage::disk('local')->assertExists($recent . '/file.tmp');

        $this->artisan('filepond:discard', ['--expiry' => 1, '--delete' => true])->assertExitCode(0);
        Storage::disk('local')->assertMissing($recent . '/file.tmp');
    }

    public function test_a_non_numeric_expiry_is_refused(): void
    {
        Storage::fake('local');

        $this->artisan('filepond:discard', ['--expiry' => 'a fortnight'])
            ->expectsOutputToContain('Invalid expiry')
            ->assertExitCode(1);
    }

    // dump:trackpictures

    /**
     * Only flux dumps get a track picture, and only those that do not have one
     * yet. Without the HxC tool configured the generation gives up, which the
     * command records rather than retrying forever.
     */
    public function test_track_pictures_are_only_attempted_for_flux_dumps(): void
    {
        Storage::fake('public');
        config(['al.hxcfe' => '/nonexistent/hxcfe']);

        $flux = Dump::factory()->inFormat('STX')->create(['track_picture' => false]);
        $plain = Dump::factory()->inFormat('ST')->create(['track_picture' => false]);
        $done = Dump::factory()->inFormat('SCP')->create(['track_picture' => true]);

        $this->artisan('dump:trackpictures')->assertExitCode(0);

        // Attempted and failed, so it stays false rather than being retried as
        // if it had never been tried
        $this->assertFalse((bool) $flux->fresh()->track_picture);
        $this->assertFalse((bool) $plain->fresh()->track_picture);
        $this->assertTrue((bool) $done->fresh()->track_picture);
    }
}
