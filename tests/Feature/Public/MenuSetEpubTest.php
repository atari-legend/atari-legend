<?php

namespace Tests\Feature\Public;

use App\Models\Crew;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * The scrolltext eBook a menu set page offers for download.
 *
 * An EPUB is a ZIP with a prescribed layout, and the whole book - cover image
 * included - is built on the fly from the database, so these open the file that
 * comes back and read what is in it rather than trusting the content type.
 *
 * The cover is drawn with imagettftext(), which needs GD built with FreeType.
 * The php.dockerfile in the development environment builds it without, so these
 * skip there rather than reporting a failure that is about the container and
 * not the code; CI installs the full extension and runs them.
 */
class MenuSetEpubTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Books written out for inspection, removed once the test is done.
     */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagettftext')) {
            $this->markTestSkipped('GD was built without FreeType, so the EPUB cover cannot be drawn.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function set(string $name = 'Automation'): MenuSet
    {
        return MenuSet::factory()->create(['name' => $name]);
    }

    private function disk(MenuSet $set, int $number, ?string $scrolltext = null): MenuDisk
    {
        $menu = Menu::factory()->create(['menu_set_id' => $set->getKey(), 'number' => $number]);

        return MenuDisk::factory()->create([
            'menu_id'    => $menu->getKey(),
            'part'       => 'A',
            'scrolltext' => $scrolltext,
        ]);
    }

    /**
     * Download the book and open it, so that what is asserted is what a reader
     * would actually be handed.
     */
    private function book(MenuSet $set): ZipArchive
    {
        $body = $this->get(route('menus.epub', $set))->assertOk()->getContent();

        $this->files[] = $file = tempnam(sys_get_temp_dir(), 'epub-test-');
        file_put_contents($file, $body);

        $zip = new ZipArchive();

        $this->assertTrue($zip->open($file) === true, 'The book is not a readable ZIP archive.');

        return $zip;
    }

    private function entryNames(ZipArchive $zip): array
    {
        $names = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }

        return $names;
    }

    /**
     * Everything the book says about the disks, in one string - which chapter
     * a line ended up in is the EPUB writer's business, not the site's.
     */
    private function text(ZipArchive $zip): string
    {
        $text = '';

        foreach ($this->entryNames($zip) as $name) {
            if (str_ends_with($name, '.xhtml') || str_ends_with($name, '.opf')) {
                $text .= $zip->getFromName($name);
            }
        }

        return $text;
    }

    public function test_the_book_is_offered_as_an_epub_download(): void
    {
        $set = $this->set();
        $this->disk($set, 1, 'Greetings to all our friends');

        $this->get(route('menus.epub', $set))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/epub+zip')
            ->assertHeader('Content-Disposition', 'attachment; filename="Scrolltexts of Automation.epub"');
    }

    /**
     * The reader picks the file apart by the magic first entry, which has to be
     * the uncompressed media type.
     */
    public function test_the_book_is_a_zip_that_starts_with_its_media_type(): void
    {
        $set = $this->set();
        $this->disk($set, 1, 'Greetings to all our friends');

        $zip = $this->book($set);

        $this->assertSame('mimetype', $zip->getNameIndex(0));
        $this->assertSame('application/epub+zip', $zip->getFromName('mimetype'));
    }

    public function test_a_scrolltext_is_in_the_book(): void
    {
        $set = $this->set();
        $this->disk($set, 1, 'Greetings to all our friends');

        $this->assertStringContainsString('Greetings to all our friends', $this->text($this->book($set)));
    }

    /**
     * Scrolltexts are plain text off a floppy: the line breaks in them are the
     * layout, and anything that looks like markup is not.
     */
    public function test_a_scrolltext_keeps_its_line_breaks_and_is_escaped(): void
    {
        $set = $this->set();
        $this->disk($set, 1, "Greetings <Union>\nand goodbye");

        $text = $this->text($this->book($set));

        $this->assertStringContainsString('Greetings &lt;Union&gt;<br>', $text);
        $this->assertStringContainsString('and goodbye', $text);
    }

    /**
     * Most disks have no scrolltext yet. They are still worth a chapter - the
     * book doubles as a listing of what is on each disk - so the missing text
     * is spelled out instead of the chapter being dropped.
     */
    public function test_a_disk_with_no_scrolltext_still_gets_a_chapter(): void
    {
        $set = $this->set();
        $this->disk($set, 1, 'Greetings to all our friends');
        $this->disk($set, 2);

        $text = $this->text($this->book($set));

        $this->assertStringContainsString('Greetings to all our friends', $text);
        $this->assertStringContainsString('No scrolltext for this disk', $text);
    }

    public function test_a_set_with_no_scrolltexts_at_all_still_produces_a_book(): void
    {
        $set = $this->set();
        $this->disk($set, 1);

        $zip = $this->book($set);

        $this->assertSame('application/epub+zip', $zip->getFromName('mimetype'));
        $this->assertStringContainsString('No scrolltext for this disk', $this->text($zip));
    }

    /**
     * A set can be listed before any of its disks are known, and the download
     * link is on the page regardless.
     */
    public function test_a_set_with_no_disks_still_produces_a_book(): void
    {
        $zip = $this->book($this->set());

        $this->assertSame('application/epub+zip', $zip->getFromName('mimetype'));
        $this->assertStringContainsString('Scrolltexts of Automation', $this->text($zip));
    }

    /**
     * The set and the crews behind it are the book's title and author, which is
     * what a library shows in its shelf.
     */
    public function test_the_book_is_titled_after_the_set_and_credited_to_its_crews(): void
    {
        $set = $this->set();
        $set->crews()->attach(Crew::factory()->create(['crew_name' => 'The Automation Crew']));
        $this->disk($set, 1, 'Greetings');

        $text = $this->text($this->book($set));

        $this->assertStringContainsString('Scrolltexts of Automation', $text);
        $this->assertStringContainsString('The Automation Crew', $text);
    }

    /**
     * The cover is generated per set rather than shipped, so an unreadable one
     * would only show up when the book is opened.
     */
    public function test_the_book_carries_a_generated_png_cover(): void
    {
        $set = $this->set();
        $this->disk($set, 1, 'Greetings');

        $cover = $this->book($set)->getFromName('OPS/images/cover.png');

        $this->assertNotFalse($cover, 'The book has no cover image.');

        $size = getimagesizefromstring($cover);

        $this->assertNotFalse($size, 'The cover is not an image.');
        $this->assertSame(IMAGETYPE_PNG, $size[2]);
    }

    /**
     * Chapters follow the set's own ordering, the same one the set page pages
     * through, so a set numbered from the newest issue reads that way too.
     */
    public function test_the_chapters_follow_the_order_the_set_is_sorted_in(): void
    {
        $set = MenuSet::factory()->sortedDescending()->create(['name' => 'Automation']);
        $first = $this->disk($set, 1, 'The oldest disk');
        $second = $this->disk($set, 2, 'The newest disk');

        $names = $this->entryNames($this->book($set));

        $this->assertLessThan(
            array_search('OPS/' . $first->getKey() . '.xhtml', $names),
            array_search('OPS/' . $second->getKey() . '.xhtml', $names)
        );
    }

    public function test_an_unknown_set_has_no_book(): void
    {
        $this->get(route('menus.epub', 9999))->assertNotFound();
    }
}
