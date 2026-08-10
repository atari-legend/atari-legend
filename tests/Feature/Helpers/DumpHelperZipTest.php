<?php

namespace Tests\Feature\Helpers;

use App\Helpers\DumpHelper;
use App\Models\Dump;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Dumps are kept zipped on disk, one file to an archive, named after the dump
 * id and format. The round trip is what matters: whatever storeDump() put in
 * has to be what getDump() takes back out.
 *
 * (Format detection is covered by tests/Unit/Helpers/DumpHelperTest.php, which
 * needs no database.)
 */
class DumpHelperZipTest extends TestCase
{
    use RefreshDatabase;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->source = tempnam(sys_get_temp_dir(), 'dump');
        file_put_contents($this->source, 'the contents of a floppy');
    }

    protected function tearDown(): void
    {
        @unlink($this->source);

        parent::tearDown();
    }

    private function dump(string $format = 'STX'): Dump
    {
        // The zip is written through the filesystem, so the directory has to
        // exist before ZipArchive can create the archive in it.
        Storage::disk('public')->makeDirectory('zips/games');

        return Dump::factory()->inFormat($format)->create();
    }

    public function test_a_stored_dump_lands_at_the_path_the_model_reports(): void
    {
        $dump = $this->dump();

        DumpHelper::storeDump($dump, $this->source);

        Storage::disk('public')->assertExists($dump->path);
        $this->assertSame('zips/games/' . $dump->getKey() . '.zip', $dump->path);
    }

    /**
     * The name inside the archive is the dump id and its lower-cased format,
     * which is what a visitor ends up with after unzipping.
     */
    public function test_the_archive_holds_one_file_named_after_the_dump(): void
    {
        $dump = $this->dump('STX');

        DumpHelper::storeDump($dump, $this->source);

        $zip = new ZipArchive();
        $zip->open(Storage::disk('public')->path($dump->path));

        $this->assertSame(1, $zip->numFiles);
        $this->assertSame($dump->getKey() . '.stx', $zip->getNameIndex(0));

        $zip->close();
    }

    public function test_a_dump_survives_the_round_trip(): void
    {
        $dump = $this->dump();
        DumpHelper::storeDump($dump, $this->source);

        $destination = tempnam(sys_get_temp_dir(), 'out');
        DumpHelper::getDump($dump, $destination);

        $this->assertSame('the contents of a floppy', file_get_contents($destination));

        unlink($destination);
    }

    public function test_each_format_keeps_its_own_extension_in_the_archive(): void
    {
        foreach (Dump::FORMATS as $format) {
            $dump = $this->dump($format);
            DumpHelper::storeDump($dump, $this->source);

            $zip = new ZipArchive();
            $zip->open(Storage::disk('public')->path($dump->path));

            $this->assertSame(
                $dump->getKey() . '.' . strtolower($format),
                $zip->getNameIndex(0),
                "Wrong name inside the archive for a {$format} dump."
            );

            $zip->close();
        }
    }

    /**
     * Without the HxC tool configured there is nothing to shell out to, and the
     * helper has to say so rather than throw - `artisan menus:check-dumps` and
     * the admin upload path both call it on machines that do not have it.
     */
    public function test_track_picture_generation_gives_up_without_the_hxc_tool(): void
    {
        config(['al.hxcfe' => '/nonexistent/hxcfe']);

        $this->assertFalse(DumpHelper::generateTrackPicture($this->dump()));
    }
}
