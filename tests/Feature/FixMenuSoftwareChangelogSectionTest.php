<?php

namespace Tests\Feature;

use App\Models\Changelog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration runs against an empty database during a test run, so it is
 * re-run here over rows that look like the ones it was written for.
 */
class FixMenuSoftwareChangelogSectionTest extends TestCase
{
    use RefreshDatabase;

    private function migrate(): void
    {
        $migration = require database_path(
            'migrations/2026_08_10_120000_fix_menu_software_changelog_section.php'
        );

        $migration->up();
    }

    private function entry(string $section, string $name): void
    {
        DB::table('change_log')->insert([
            'action'           => Changelog::INSERT,
            'section'          => $section,
            'section_id'       => 1,
            'section_name'     => $name,
            'sub_section'      => 'Software',
            'sub_section_id'   => 1,
            'sub_section_name' => $name,
            'user_id'          => 1,
            'timestamp'        => Carbon::parse('2025-01-01')->timestamp,
        ]);
    }

    public function test_the_misspelt_section_is_corrected(): void
    {
        $this->entry('Menu Softwre', 'Xtracker');
        $this->entry('Menu Softwre', 'Noisetracker');

        $this->migrate();

        $this->assertSame(0, DB::table('change_log')->where('section', 'Menu Softwre')->count());
        $this->assertSame(2, DB::table('change_log')->where('section', 'Menu Software')->count());
    }

    /**
     * Entries already written with the correct spelling are left alone, and
     * merge with the corrected ones.
     */
    public function test_correctly_spelt_entries_are_untouched(): void
    {
        $this->entry('Menu Softwre', 'Xtracker');
        $this->entry('Menu Software', 'Protracker');

        $this->migrate();

        $this->assertSame(2, DB::table('change_log')->where('section', 'Menu Software')->count());
    }

    public function test_other_sections_are_not_touched(): void
    {
        $this->entry('Games', 'Xenon');
        $this->entry('Menus', 'Automation');

        $this->migrate();

        $this->assertSame(1, DB::table('change_log')->where('section', 'Games')->count());
        $this->assertSame(1, DB::table('change_log')->where('section', 'Menus')->count());
    }

    /**
     * Nothing else about the entry changes - only the section it is filed
     * under.
     */
    public function test_the_rest_of_the_entry_is_left_alone(): void
    {
        $this->entry('Menu Softwre', 'Xtracker');

        $this->migrate();

        $row = DB::table('change_log')->sole();

        $this->assertSame('Xtracker', $row->section_name);
        $this->assertSame('Software', $row->sub_section);
        $this->assertSame(Changelog::INSERT, $row->action);
    }

    public function test_running_it_twice_is_harmless(): void
    {
        $this->entry('Menu Softwre', 'Xtracker');

        $this->migrate();
        $this->migrate();

        $this->assertSame(1, DB::table('change_log')->where('section', 'Menu Software')->count());
    }
}
