<?php

namespace Tests\Feature\Console;

use App\Console\Commands\GenerateSNDHJson;
use App\Models\SndhArchive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * The two SNDH commands: one fetches and unpacks the archives, the other walks
 * the unpacked tree and reads each tune's header into a JSON index.
 *
 * SNDH files are binary, so the fixtures here are built byte by byte. The
 * format is: twelve bytes of boot sector, the marker 'SNDH', then four-byte
 * tags each followed by a NUL-terminated value, ending at 'HDNS'.
 */
class SndhCommandsTest extends TestCase
{
    use RefreshDatabase;

    private const ARCHIVE = 'test-archive';

    protected function setUp(): void
    {
        parent::setUp();

        // The migrations ship the real archives; drop them so only the fixture
        // below is processed
        SndhArchive::query()->delete();
    }

    protected function tearDown(): void
    {
        // GenerateSNDHJson writes to a path relative to the working directory,
        // which is the project itself - so clean up after it
        @unlink(base_path(GenerateSNDHJson::SONGS_JSON_PATH . '/songs-' . self::ARCHIVE . '.json'));

        parent::tearDown();
    }

    private function archive(string $url = 'https://example.org/sndh.zip'): SndhArchive
    {
        // forceCreate: SndhArchive declares no fillable columns
        return SndhArchive::forceCreate(['id' => self::ARCHIVE, 'download_url' => $url]);
    }

    /**
     * A minimal but valid SNDH file.
     *
     * @param  array<string, string>  $tags  Four-character tag => value
     */
    private function sndhFile(array $tags): string
    {
        $body = str_repeat("\0", 12) . 'SNDH';

        foreach ($tags as $tag => $value) {
            $body .= $tag . $value . "\0";
        }

        return $body . 'HDNS';
    }

    private function putTune(string $path, string $contents): void
    {
        $full = Storage::disk('public')->path('sndh/' . self::ARCHIVE . '/sndh_lf/' . $path);

        @mkdir(dirname($full), 0777, true);
        file_put_contents($full, $contents);
    }

    // sndh:fetch

    public function test_fetch_downloads_and_unpacks_an_archive(): void
    {
        Storage::fake('public');

        $archive = $this->archive();

        // Put the ZIP where the command expects it, so it skips the download
        // and goes straight to unpacking
        $zipPath = Storage::disk('public')->path('sndh') . '/' . $archive->getKey() . '.zip';
        @mkdir(dirname($zipPath), 0777, true);

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFromString('sndh_lf/tune.sndh', 'contents');
        $zip->close();

        $this->artisan('sndh:fetch')
            ->expectsOutputToContain('already exists, skipping download')
            ->expectsOutputToContain('Extracted SNDH archive');

        $this->assertFileExists(
            Storage::disk('public')->path('sndh/' . $archive->getKey() . '/sndh_lf/tune.sndh')
        );
    }

    public function test_fetch_skips_an_archive_that_is_already_unpacked(): void
    {
        Storage::fake('public');

        $archive = $this->archive();

        $base = Storage::disk('public')->path('sndh');
        @mkdir($base . '/' . $archive->getKey(), 0777, true);
        file_put_contents($base . '/' . $archive->getKey() . '.zip', 'not a real zip');

        $this->artisan('sndh:fetch')
            ->expectsOutputToContain('already exists, skipping extraction');
    }

    /**
     * A failed download must not leave a half-written ZIP behind, or the next
     * run would take it for a complete one and try to unpack it.
     */
    public function test_a_failed_download_is_cleaned_up(): void
    {
        Storage::fake('public');

        Http::fake(['*' => Http::response('Not found', 404)]);

        $archive = $this->archive();

        $this->artisan('sndh:fetch')
            ->expectsOutputToContain('Error downloading archive');

        $this->assertFileDoesNotExist(
            Storage::disk('public')->path('sndh') . '/' . $archive->getKey() . '.zip'
        );
    }

    // sndh:generate-json

    public function test_generate_json_reports_a_missing_folder(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->artisan('sndh:generate-json')
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(0);
    }

    public function test_generate_json_reads_the_tags_of_a_tune(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('Jochen Hippel/Wings of Death.sndh', $this->sndhFile([
            'TITL' => 'Wings of Death',
            'COMM' => 'Jochen Hippel',
            'RIPP' => 'Someone',
            'CONV' => 'Someone else',
            'YEAR' => '1990',
        ]));

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $songs = $this->songs();
        $tune = $songs['/Jochen Hippel/Wings of Death'];

        $this->assertSame('Wings of Death', $tune['title']);
        $this->assertSame('Jochen Hippel', $tune['composer']);
        $this->assertSame('Someone', $tune['ripper']);
        $this->assertSame('Someone else', $tune['converter']);
        $this->assertSame(1990, $tune['year']);
    }

    /**
     * The subtune count arrives as part of the tag itself - '##04' means four -
     * and later tags read that many values.
     */
    public function test_generate_json_reads_the_subtune_count(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('multi.sndh', $this->sndhFile([
            'TITL' => 'Several tunes',
            '##04' => '',
        ]));

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $this->assertSame(4, $this->songs()['/multi']['subtunes']);
    }

    public function test_an_empty_year_is_left_out(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('undated.sndh', $this->sndhFile([
            'TITL' => 'Undated',
            'YEAR' => '',
        ]));

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $this->assertArrayNotHasKey('year', $this->songs()['/undated']);
    }

    /**
     * Titles are stored in CP-437, the Atari's character set, and have to come
     * out as UTF-8.
     */
    public function test_titles_are_converted_from_cp437(): void
    {
        Storage::fake('public');

        $this->archive();

        // 0x81 is 'ü' in CP-437
        $this->putTune('umlaut.sndh', $this->sndhFile([
            'TITL' => "M\x81nchen",
        ]));

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $this->assertSame('München', $this->songs()['/umlaut']['title']);
    }

    public function test_a_file_without_the_sndh_marker_is_skipped_with_a_warning(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('good.sndh', $this->sndhFile(['TITL' => 'Good']));
        $this->putTune('bad.sndh', str_repeat("\0", 12) . 'JUNK' . 'nothing here');

        $this->artisan('sndh:generate-json')
            ->expectsOutputToContain('does not have the SNDH marker')
            ->assertExitCode(0);

        $songs = $this->songs();

        $this->assertArrayHasKey('/good', $songs);
        $this->assertArrayNotHasKey('/bad', $songs);
    }

    public function test_files_that_are_not_tunes_are_ignored(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('tune.sndh', $this->sndhFile(['TITL' => 'A tune']));
        $this->putTune('readme.txt', 'Not a tune');

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $this->assertSame(['/tune'], array_keys($this->songs()));
    }

    /**
     * The index is sorted, so it does not churn between runs.
     */
    public function test_the_index_is_sorted_by_path(): void
    {
        Storage::fake('public');

        $this->archive();

        $this->putTune('Zoo/last.sndh', $this->sndhFile(['TITL' => 'Last']));
        $this->putTune('Alpha/first.sndh', $this->sndhFile(['TITL' => 'First']));

        $this->artisan('sndh:generate-json')->assertExitCode(0);

        $this->assertSame(['/Alpha/first', '/Zoo/last'], array_keys($this->songs()));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function songs(): array
    {
        $path = base_path(GenerateSNDHJson::SONGS_JSON_PATH . '/songs-' . self::ARCHIVE . '.json');

        $this->assertFileExists($path);

        return json_decode(file_get_contents($path), true);
    }
}
