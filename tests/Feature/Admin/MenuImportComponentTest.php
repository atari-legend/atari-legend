<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MenuImport;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\Individual;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskCondition;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use App\Models\GameRelease;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * The interactive half of the bulk menu import: uploading a filled-in template,
 * the auto-matching that runs over it, and the corrections an administrator
 * makes on the review screen. MenuImportTest covers the commit itself.
 */
class MenuImportComponentTest extends AdminTestCase
{
    /**
     * Column letters in the same order as MenuImportTemplate::COLUMNS.
     * A..E = Menu, F..K = Disk, L..Q = Content.
     */
    private const COLS = [
        'new_menu'        => 'A', 'menu_number' => 'B', 'menu_issue' => 'C', 'menu_version' => 'D', 'menu_date' => 'E',
        'new_disk'        => 'F', 'disk_part' => 'G', 'disk_condition' => 'H', 'disk_donated_by' => 'I', 'disk_notes' => 'J', 'disk_scrolltext' => 'K',
        'content_order'   => 'L', 'content_name' => 'M', 'content_type' => 'N',
        'content_subtype' => 'O', 'content_version' => 'P', 'content_requirements' => 'Q',
    ];

    private MenuSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        // The upload lands on the default disk before the parser reads it.
        Storage::fake('local');

        $this->set = MenuSet::factory()->create();
    }

    /**
     * Turn rows keyed by the template's column keys into an uploadable .xlsx,
     * the way a filled-in copy of the downloaded template arrives.
     */
    private function spreadsheet(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Import');

        // Data starts on row 3 (rows 1 & 2 are headers).
        $excelRow = 3;
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $sheet->setCellValue(self::COLS[$key] . $excelRow, $value);
            }
            $excelRow++;
        }

        $path = tempnam(sys_get_temp_dir(), 'menu-import');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $content = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('import.xlsx', $content);
    }

    /**
     * The "Intact" disk condition. Conditions are reference data the migrations
     * ship, so the sheets name one of those rather than one of their own.
     */
    private function intact(): MenuDiskCondition
    {
        return MenuDiskCondition::where('name', 'Intact')->firstOrFail();
    }

    /** Upload the given rows and hand back the component on the review screen. */
    private function review(array $rows)
    {
        return Livewire::test(MenuImport::class, ['set' => $this->set])
            ->set('file', $this->spreadsheet($rows));
    }

    // -----------------------------------------------------------------
    //  Upload
    // -----------------------------------------------------------------

    public function test_uploading_a_filled_in_template_populates_the_review(): void
    {
        $game = Game::factory()->named('Populous')->create();
        $software = MenuSoftware::factory()->named('Ultimate Ripper')->create();
        $condition = $this->intact();
        $bob = Individual::factory()->create(['ind_name' => 'Bob']);

        $component = $this->review([
            [
                'new_menu'             => 'x', 'new_disk' => 'x',
                'menu_number'          => 12, 'menu_version' => 'final', 'menu_date' => '1990-06-05',
                'disk_part'            => 'A', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob',
                'disk_notes'           => 'Sticker missing', 'disk_scrolltext' => 'Greetings!',
                'content_order'        => 1, 'content_name' => 'Populous', 'content_type' => 'Game',
                'content_version'      => '1.2', 'content_requirements' => '512KB',
            ],
            [
                'content_order' => 2, 'content_name' => 'Ultimate Ripper', 'content_type' => 'Software',
            ],
        ]);

        $component->assertSet('reviewing', true)->assertDispatched('menu-import-rendered');

        $this->assertSame('12', $component->get('menus.0.number'));
        $this->assertSame('final', $component->get('menus.0.version'));
        $this->assertSame('1990-06-05', $component->get('menus.0.date'));

        $this->assertSame('A', $component->get('menus.0.disks.0.part'));
        $this->assertEquals($condition->id, $component->get('menus.0.disks.0.condition_id'));
        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame('Sticker missing', $component->get('menus.0.disks.0.notes'));
        $this->assertSame('Greetings!', $component->get('menus.0.disks.0.scrolltext'));

        // The game row: matched, and both the sheet's name and the live search
        // term start out as what the spreadsheet said.
        $this->assertEquals($game->getKey(), $component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertSame('Populous', $component->get('menus.0.disks.0.contents.0.game_name'));
        $this->assertSame('Populous', $component->get('menus.0.disks.0.contents.0.name'));
        $this->assertSame('Populous', $component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame('512KB', $component->get('menus.0.disks.0.contents.0.requirements'));
        $this->assertSame('new_release', $component->get('menus.0.disks.0.contents.0.link_mode'));

        // The software row goes down the other branch of the resolver.
        $this->assertEquals($software->id, $component->get('menus.0.disks.0.contents.1.software_id'));
        $this->assertSame('Ultimate Ripper', $component->get('menus.0.disks.0.contents.1.software_name'));
        $this->assertNull($component->get('menus.0.disks.0.contents.1.game_id'));
        $this->assertSame('software', $component->get('menus.0.disks.0.contents.1.link_mode'));

        $this->assertSame(0, $component->instance()->getErrorCount());
    }

    public function test_uploading_something_that_is_not_a_spreadsheet_is_rejected(): void
    {
        Livewire::test(MenuImport::class, ['set' => $this->set])
            ->set('file', UploadedFile::fake()->create('menus.txt', 8))
            ->assertHasErrors(['file' => 'extensions'])
            ->assertSet('reviewing', false)
            ->assertSet('menus', []);
    }

    public function test_uploading_matches_menus_and_disks_that_already_exist_in_the_set(): void
    {
        $menu = Menu::create(['number' => '3', 'menu_set_id' => $this->set->id]);
        $disk = MenuDisk::create(['part' => 'A', 'menu_id' => $menu->id]);

        $component = $this->review([
            // Menu 3 / disk A: both already there.
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 3, 'disk_part' => 'A', 'disk_condition' => 'Intact'],
            // Same menu, a disk it does not have yet.
            ['new_disk' => 'x', 'disk_part' => 'B', 'disk_condition' => 'Intact'],
            // A menu the set does not have: its disk cannot match either.
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 4, 'disk_part' => 'A', 'disk_condition' => 'Intact'],
        ]);

        $this->assertEquals($menu->id, $component->get('menus.0.existing_menu_id'));
        $this->assertSame($menu->label, $component->get('menus.0.existing_menu_label'));
        $this->assertEquals($disk->id, $component->get('menus.0.disks.0.existing_disk_id'));
        $this->assertSame($disk->label, $component->get('menus.0.disks.0.existing_disk_label'));

        $this->assertNull($component->get('menus.0.disks.1.existing_disk_id'));

        $this->assertNull($component->get('menus.1.existing_menu_id'));
        $this->assertNull($component->get('menus.1.disks.0.existing_disk_id'));
    }

    // -----------------------------------------------------------------
    //  Auto-matching
    // -----------------------------------------------------------------

    public function test_games_are_matched_by_name_or_aka_and_left_unresolved_otherwise(): void
    {
        $populous = Game::factory()->named('Populous')->create();
        $rick = Game::factory()->named('Rick Dangerous')->create();
        GameAka::create(['game_id' => $rick->getKey(), 'aka_name' => 'Rick Hazardous']);
        Game::factory()->named('Twin')->create();
        Game::factory()->named('Twin')->create(['slug' => 'twin-other']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Populous', 'content_type' => 'Game'],
            ['content_order' => 2, 'content_name' => 'Rick Hazardous', 'content_type' => 'Game'],
            ['content_order' => 3, 'content_name' => 'Twin', 'content_type' => 'Game'],
            ['content_order' => 4, 'content_name' => 'Nonesuch', 'content_type' => 'Game'],
        ]);

        $this->assertEquals($populous->getKey(), $component->get('menus.0.disks.0.contents.0.game_id'));

        // An AKA resolves to its game, labelled so the reviewer can see why.
        $this->assertEquals($rick->getKey(), $component->get('menus.0.disks.0.contents.1.game_id'));
        $this->assertSame('Rick Hazardous (AKA)', $component->get('menus.0.disks.0.contents.1.game_name'));

        // Two games share the third name, so nothing is picked automatically.
        $this->assertNull($component->get('menus.0.disks.0.contents.2.game_id'));
        $this->assertCount(2, $component->get('menus.0.disks.0.contents.2.candidates'));

        // The fourth matches nothing at all: no pick and nothing to offer.
        $this->assertNull($component->get('menus.0.disks.0.contents.3.game_id'));
        $this->assertSame([], $component->get('menus.0.disks.0.contents.3.candidates'));

        $this->assertSame(2, $component->instance()->getErrorCount());
        $component->assertSee('Several matches found')
            ->assertSee('was not found');
    }

    public function test_software_is_matched_by_name_and_left_unresolved_otherwise(): void
    {
        $ripper = MenuSoftware::factory()->named('Ultimate Ripper')->create();
        MenuSoftware::factory()->named('Selector')->create();
        MenuSoftware::factory()->named('Selector')->create();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Ultimate Ripper', 'content_type' => 'Software'],
            ['content_order' => 2, 'content_name' => 'Selector', 'content_type' => 'Software'],
            ['content_order' => 3, 'content_name' => 'Nonesuch', 'content_type' => 'Software'],
        ]);

        $this->assertEquals($ripper->id, $component->get('menus.0.disks.0.contents.0.software_id'));

        $this->assertNull($component->get('menus.0.disks.0.contents.1.software_id'));
        $this->assertCount(2, $component->get('menus.0.disks.0.contents.1.candidates'));

        $this->assertNull($component->get('menus.0.disks.0.contents.2.software_id'));
        $this->assertSame([], $component->get('menus.0.disks.0.contents.2.candidates'));

        $this->assertSame(2, $component->instance()->getErrorCount());
    }

    public function test_disk_conditions_are_matched_by_name(): void
    {
        $intact = $this->intact();
        $damaged = MenuDiskCondition::where('name', 'Slightly damaged')->firstOrFail();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_part' => 'A', 'disk_condition' => 'Intact'],
            ['new_disk' => 'x', 'disk_part' => 'B', 'disk_condition' => 'Slightly damaged'],
            ['new_disk' => 'x', 'disk_part' => 'C', 'disk_condition' => 'Slightly chewed'],
            ['new_disk' => 'x', 'disk_part' => 'D'],
        ]);

        $this->assertEquals($intact->id, $component->get('menus.0.disks.0.condition_id'));
        $this->assertEquals($damaged->id, $component->get('menus.0.disks.1.condition_id'));

        // A condition the site does not have, and no condition at all, both
        // leave the disk unresolved — and a new disk must have one.
        $this->assertNull($component->get('menus.0.disks.2.condition_id'));
        $this->assertNull($component->get('menus.0.disks.3.condition_id'));
        $this->assertSame(2, $component->instance()->getErrorCount());

        // Choosing a condition on the review screen resolves it there and then.
        $component->set('menus.0.disks.2.condition', 'Intact');
        $component->set('menus.0.disks.3.condition', 'Intact');

        $this->assertEquals($intact->id, $component->get('menus.0.disks.2.condition_id'));
        $this->assertEquals($intact->id, $component->get('menus.0.disks.3.condition_id'));
        $this->assertSame(0, $component->instance()->getErrorCount());
    }

    public function test_donators_are_matched_by_name_and_through_the_autocomplete_decoration(): void
    {
        $bob = Individual::factory()->create(['ind_name' => 'Bob']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_part' => 'A', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob'],
            // A name pasted straight out of the individuals autocomplete, which
            // decorates its suggestions with "(aka: …)" and "[games…]" suffixes.
            ['new_disk' => 'x', 'disk_part' => 'B', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob (aka: Bobby)'],
            ['new_disk' => 'x', 'disk_part' => 'C', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob [Populous]'],
            ['new_disk' => 'x', 'disk_part' => 'D', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Nobody'],
            ['new_disk' => 'x', 'disk_part' => 'E', 'disk_condition' => 'Intact'],
        ]);

        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.0.donated_by_id'));
        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.1.donated_by_id'));
        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.2.donated_by_id'));

        // A donator nobody knows is a problem; a blank one is not.
        $this->assertNull($component->get('menus.0.disks.3.donated_by_id'));
        $this->assertNull($component->get('menus.0.disks.4.donated_by_id'));
        $this->assertSame(1, $component->instance()->getErrorCount());
        $component->assertSee('Unknown individual');
    }

    public function test_menu_dates_are_normalised_from_the_forms_the_sheet_allows(): void
    {
        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 1, 'menu_date' => 1990, 'disk_condition' => 'Intact'],
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 2, 'menu_date' => '1990-06', 'disk_condition' => 'Intact'],
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 3, 'menu_date' => '5 June 1990', 'disk_condition' => 'Intact'],
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 4, 'menu_date' => 'sometime in the 90s', 'disk_condition' => 'Intact'],
            ['new_menu' => 'x', 'new_disk' => 'x', 'menu_number' => 5, 'disk_condition' => 'Intact'],
        ]);

        // A year or a year-month is padded rather than rejected: that is how
        // most of the menus in the sheets are dated.
        $this->assertSame('1990-01-01', $component->get('menus.0.date'));
        $this->assertSame('1990-06-01', $component->get('menus.1.date'));
        $this->assertSame('1990-06-05', $component->get('menus.2.date'));
        $this->assertNull($component->get('menus.3.date'));
        $this->assertNull($component->get('menus.4.date'));
    }

    public function test_the_link_mode_is_chosen_from_the_sub_type_and_the_menus_main_releases(): void
    {
        Game::factory()->named('Populous')->create();
        Game::factory()->named('Nebulus')->create();
        MenuSoftware::factory()->named('Ultimate Ripper')->create();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Populous', 'content_type' => 'Game'],
            ['content_order' => 2, 'content_name' => 'Populous', 'content_type' => 'Game', 'content_subtype' => 'Docs'],
            ['content_order' => 3, 'content_name' => 'Nebulus', 'content_type' => 'Game', 'content_subtype' => 'Docs'],
            ['content_order' => 4, 'content_name' => 'Ultimate Ripper', 'content_type' => 'Software'],
        ]);

        // No sub-type: the game itself, so a release of it goes on the menu.
        $this->assertSame('new_release', $component->get('menus.0.disks.0.contents.0.link_mode'));
        // Sub-typed, and its game is on the menu: an extra of that release.
        $this->assertSame('extra', $component->get('menus.0.disks.0.contents.1.link_mode'));
        // Sub-typed, but its game is not on the menu: standalone.
        $this->assertSame('standalone', $component->get('menus.0.disks.0.contents.2.link_mode'));
        $this->assertSame('software', $component->get('menus.0.disks.0.contents.3.link_mode'));

        $this->assertSame(0, $component->instance()->getErrorCount());
    }

    // -----------------------------------------------------------------
    //  Manual correction
    // -----------------------------------------------------------------

    public function test_changing_the_link_mode_re_checks_what_the_row_needs(): void
    {
        Game::factory()->named('Populous')->create();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Populous', 'content_type' => 'Game'],
        ]);

        $this->assertSame(0, $component->instance()->getErrorCount());

        // As an extra, the row needs a sub-type and a main release to hang off.
        $component->set('menus.0.disks.0.contents.0.link_mode', 'extra');

        $this->assertSame(2, $component->instance()->getErrorCount());
        $component->assertSee('An extra needs a sub-type')
            ->assertDispatched('menu-import-rendered');

        // A standalone needs the sub-type but no release of its own.
        $component->set('menus.0.disks.0.contents.0.link_mode', 'standalone');

        $this->assertSame(1, $component->instance()->getErrorCount());
        $component->assertSee('A standalone game doc / extra needs a sub-type');

        $component->set('menus.0.disks.0.contents.0.subtype', 'Docs');

        $this->assertSame(0, $component->instance()->getErrorCount());
    }

    public function test_an_ambiguous_game_can_be_picked_from_the_autocomplete_and_cleared_again(): void
    {
        Game::factory()->named('Twin')->create();
        $wanted = Game::factory()->named('Twin')->create(['slug' => 'twin-other']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Twin', 'content_type' => 'Game'],
        ]);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertSame(1, $component->instance()->getErrorCount());

        // The autocomplete posts the id of the suggestion, as a string.
        $component->call('updateGame', 0, 0, 0, (string) $wanted->getKey());

        $this->assertEquals($wanted->getKey(), $component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertSame('Twin', $component->get('menus.0.disks.0.contents.0.game_name'));
        $this->assertSame('Twin', $component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame([], $component->get('menus.0.disks.0.contents.0.candidates'));
        $this->assertSame(0, $component->instance()->getErrorCount());

        // The cross next to the field empties the row again.
        $component->call('updateGame', 0, 0, 0, null);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_name'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame(1, $component->instance()->getErrorCount());
        $component->assertSee('Choose a game');
    }

    public function test_typing_a_game_name_re_resolves_the_row(): void
    {
        $nebulus = Game::factory()->named('Nebulus')->create();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Nonesuch', 'content_type' => 'Game'],
        ]);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));

        // The games autocomplete appends " [developers]" to its suggestions,
        // which has to come back off before the name can be matched.
        $component->call('setGameSearch', 0, 0, 0, 'Nebulus [Hewson]');

        $this->assertEquals($nebulus->getKey(), $component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertSame('Nebulus', $component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame(0, $component->instance()->getErrorCount());

        // A name that matches nothing unlinks the row and is kept as typed, so
        // the reviewer can see what was searched for.
        $component->call('setGameSearch', 0, 0, 0, 'Still Nonesuch');

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertSame('Still Nonesuch', $component->get('menus.0.disks.0.contents.0.query'));

        // Emptying the field leaves nothing to search for.
        $component->call('setGameSearch', 0, 0, 0, '   ');

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.query'));

        $component->call('setGameSearch', 0, 0, 0, null);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.query'));
    }

    /**
     * The precise pick an administrator made must survive the free-text change
     * event fired by the same interaction: re-matching "Twin" on its name alone
     * would land on whichever of the two games sharing it comes first.
     */
    public function test_a_typed_name_that_still_matches_the_linked_game_leaves_it_alone(): void
    {
        Game::factory()->named('Twin')->create();
        $wanted = Game::factory()->named('Twin')->create(['slug' => 'twin-other']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Twin', 'content_type' => 'Game'],
        ]);

        $component->call('updateGame', 0, 0, 0, (string) $wanted->getKey());
        $component->call('setGameSearch', 0, 0, 0, 'Twin [Reline]');

        $this->assertEquals($wanted->getKey(), $component->get('menus.0.disks.0.contents.0.game_id'));

        // Typing something else, on the other hand, does re-resolve.
        $component->call('setGameSearch', 0, 0, 0, 'Twin Peaks');

        $this->assertNull($component->get('menus.0.disks.0.contents.0.game_id'));
    }

    public function test_an_ambiguous_software_row_can_be_picked_typed_and_cleared(): void
    {
        MenuSoftware::factory()->named('Selector')->create();
        $wanted = MenuSoftware::factory()->named('Selector')->create();
        $other = MenuSoftware::factory()->named('Ultimate Ripper')->create();

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Selector', 'content_type' => 'Software'],
        ]);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.software_id'));

        $component->call('updateSoftware', 0, 0, 0, (string) $wanted->id);

        $this->assertEquals($wanted->id, $component->get('menus.0.disks.0.contents.0.software_id'));
        $this->assertSame('Selector', $component->get('menus.0.disks.0.contents.0.software_name'));
        $this->assertSame('Selector', $component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame([], $component->get('menus.0.disks.0.contents.0.candidates'));
        $this->assertSame(0, $component->instance()->getErrorCount());

        // The change event that follows the pick must not undo it by matching
        // the shared name again.
        $component->call('setSoftwareSearch', 0, 0, 0, 'Selector');

        $this->assertEquals($wanted->id, $component->get('menus.0.disks.0.contents.0.software_id'));

        // Typing another name re-resolves; typing an unknown one unlinks.
        $component->call('setSoftwareSearch', 0, 0, 0, ' Ultimate Ripper ');

        $this->assertEquals($other->id, $component->get('menus.0.disks.0.contents.0.software_id'));

        $component->call('setSoftwareSearch', 0, 0, 0, 'Nonesuch');

        $this->assertNull($component->get('menus.0.disks.0.contents.0.software_id'));
        $this->assertSame('Nonesuch', $component->get('menus.0.disks.0.contents.0.query'));

        $component->call('setSoftwareSearch', 0, 0, 0, '  ');

        $this->assertNull($component->get('menus.0.disks.0.contents.0.software_id'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.query'));

        // And the cross empties the row.
        $component->call('updateSoftware', 0, 0, 0, null);

        $this->assertNull($component->get('menus.0.disks.0.contents.0.software_id'));
        $this->assertNull($component->get('menus.0.disks.0.contents.0.query'));
        $this->assertSame(1, $component->instance()->getErrorCount());
        $component->assertSee('Choose a software entry');
    }

    public function test_the_donator_can_be_picked_typed_and_cleared(): void
    {
        $bob = Individual::factory()->create(['ind_name' => 'Bob']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Nobody'],
        ]);

        $this->assertNull($component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame(1, $component->instance()->getErrorCount());

        $component->call('updateIndividual', 0, 0, (string) $bob->getKey());

        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame('Bob', $component->get('menus.0.disks.0.donated_by'));
        $this->assertSame(0, $component->instance()->getErrorCount());

        // Typing over the field re-resolves it, decoration and all.
        $component->call('setDonatedBy', 0, 0, 'Nobody Else');

        $this->assertNull($component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame('Nobody Else', $component->get('menus.0.disks.0.donated_by'));

        $component->call('setDonatedBy', 0, 0, 'Bob (aka: Bobby)');

        $this->assertEquals($bob->getKey(), $component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame('Bob', $component->get('menus.0.disks.0.donated_by'));

        // Emptying the autocomplete's hidden id field unlinks the donator…
        $component->call('updateIndividual', 0, 0, '');

        $this->assertNull($component->get('menus.0.disks.0.donated_by'));
        $this->assertNull($component->get('menus.0.disks.0.donated_by_id'));

        // …as does the cross next to it. Either is a valid state to import in:
        // an unknown donator is a problem, no donator is not.
        $component->call('setDonatedBy', 0, 0, 'Bob');
        $component->call('clearIndividual', 0, 0);

        $this->assertNull($component->get('menus.0.disks.0.donated_by'));
        $this->assertNull($component->get('menus.0.disks.0.donated_by_id'));
        $this->assertSame(0, $component->instance()->getErrorCount());
    }

    /**
     * Two individuals can share a name, so the id picked from the autocomplete
     * must not be re-derived from the name the same interaction leaves in the
     * field — that would silently credit the donation to the wrong person.
     */
    public function test_a_typed_donator_that_still_matches_the_linked_individual_leaves_it_alone(): void
    {
        Individual::factory()->create(['ind_name' => 'Bob']);
        $wanted = Individual::factory()->create(['ind_name' => 'Bob']);

        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact'],
        ]);

        $component->call('updateIndividual', 0, 0, (string) $wanted->getKey());
        $component->call('setDonatedBy', 0, 0, 'Bob (aka: Bobby)');

        $this->assertEquals($wanted->getKey(), $component->get('menus.0.disks.0.donated_by_id'));
    }

    // -----------------------------------------------------------------
    //  End to end
    // -----------------------------------------------------------------

    public function test_a_corrected_upload_imports_the_menu_disk_and_contents(): void
    {
        $game = Game::factory()->named('Nebulus')->create();
        $docs = Game::factory()->named('Rick Dangerous')->create();
        $software = MenuSoftware::factory()->named('Ultimate Ripper')->create();
        $condition = $this->intact();
        $bob = Individual::factory()->create(['ind_name' => 'Bob']);

        // The sheet has the first game under a name the site does not know.
        $component = $this->review([
            [
                'new_menu'      => 'x', 'new_disk' => 'x', 'menu_number' => 9, 'menu_date' => 1990,
                'disk_part'     => 'A', 'disk_condition' => 'Intact', 'disk_donated_by' => 'Bob',
                'content_order' => 1, 'content_name' => 'Nebulous', 'content_type' => 'Game',
            ],
            ['content_order' => 2, 'content_name' => 'Ultimate Ripper', 'content_type' => 'Software'],
            ['content_order' => 3, 'content_name' => 'Rick Dangerous', 'content_type' => 'Game', 'content_subtype' => 'Docs'],
        ]);

        $this->assertSame(1, $component->instance()->getErrorCount());

        $component->call('setGameSearch', 0, 0, 0, 'Nebulus [Hewson]');
        $this->assertSame(0, $component->instance()->getErrorCount());

        $component->call('runImport')
            ->assertRedirect(route('admin.menus.sets.edit', $this->set));

        $menu = Menu::where('menu_set_id', $this->set->id)->firstOrFail();
        $this->assertEquals('9', $menu->number);
        $this->assertSame('1990-01-01', $menu->date->format('Y-m-d'));

        $disk = MenuDisk::where('menu_id', $menu->id)->firstOrFail();
        $this->assertSame('A', $disk->part);
        $this->assertEquals($condition->id, $disk->menu_disk_condition_id);
        $this->assertEquals($bob->getKey(), $disk->donated_by_individual_id);

        // One row per content, each linked the way its mode says: the corrected
        // game through a new release, the software and the docs directly.
        $this->assertSame(3, MenuDiskContent::where('menu_disk_id', $disk->id)->count());
        $release = GameRelease::where('game_id', $game->getKey())->sole();
        $this->assertSame(1, MenuDiskContent::where('game_release_id', $release->id)->count());
        $this->assertSame(1, MenuDiskContent::where('menu_software_id', $software->id)->count());
        $this->assertSame(1, MenuDiskContent::where('game_id', $docs->getKey())->count());

        $this->assertChangelog(Changelog::INSERT, 'Menus');
    }

    public function test_importing_while_problems_remain_is_refused(): void
    {
        $component = $this->review([
            ['new_menu' => 'x', 'new_disk' => 'x', 'disk_condition' => 'Intact', 'content_order' => 1, 'content_name' => 'Nonesuch', 'content_type' => 'Game'],
        ]);

        $component->call('runImport')
            ->assertNoRedirect()
            ->assertSet('importAttempted', true)
            ->assertDispatched('menu-import-has-errors')
            ->assertSee('before importing');

        $this->assertSame(0, Menu::count());
        $this->assertSame(0, MenuDisk::count());
        $this->assertNoChangelog();
    }
}
