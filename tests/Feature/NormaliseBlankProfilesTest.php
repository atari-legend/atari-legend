<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The migration runs against an empty database during a test run, so it is
 * re-run here over rows that look like the ones it was written for.
 */
class NormaliseBlankProfilesTest extends TestCase
{
    use RefreshDatabase;

    private function migrate(): void
    {
        $migration = require database_path('migrations/2026_08_09_100000_normalise_blank_profiles.php');
        $migration->up();
    }

    private function individuals(int $count): void
    {
        foreach (range(1, $count) as $id) {
            DB::table('individuals')->insert(['id' => $id, 'ind_name' => 'Individual ' . $id]);
        }
    }

    private function companies(int $count): void
    {
        foreach (range(1, $count) as $id) {
            DB::table('pub_dev')->insert(['pub_dev_id' => $id, 'pub_dev_name' => 'Company ' . $id]);
        }
    }

    public function test_blank_individual_profiles_become_null(): void
    {
        $this->individuals(6);

        DB::table('individual_text')->insert([
            ['ind_id' => 1, 'ind_profile' => 'Member in Dune'],
            ['ind_id' => 2, 'ind_profile' => "\t\t\t\t\t\t\t\t"],
            ['ind_id' => 3, 'ind_profile' => ''],
            ['ind_id' => 4, 'ind_profile' => "  \r\n  "],
            ['ind_id' => 5, 'ind_profile' => null],
            // Whitespace around real content is left alone
            ['ind_id' => 6, 'ind_profile' => "\tCoder in DHS\n"],
        ]);

        $this->migrate();

        $profiles = DB::table('individual_text')->orderBy('ind_id')->pluck('ind_profile', 'ind_id');

        $this->assertSame('Member in Dune', $profiles[1]);
        $this->assertNull($profiles[2]);
        $this->assertNull($profiles[3]);
        $this->assertNull($profiles[4]);
        $this->assertNull($profiles[5]);
        $this->assertSame("\tCoder in DHS\n", $profiles[6]);
    }

    public function test_blank_emails_and_image_extensions_become_null(): void
    {
        $this->individuals(2);

        DB::table('individual_text')->insert([
            ['ind_id' => 1, 'ind_email' => '', 'ind_imgext' => ''],
            ['ind_id' => 2, 'ind_email' => 'someone@example.org', 'ind_imgext' => 'png'],
        ]);

        $this->migrate();

        $blank = DB::table('individual_text')->where('ind_id', 1)->first();
        $set = DB::table('individual_text')->where('ind_id', 2)->first();

        $this->assertNull($blank->ind_email);
        $this->assertNull($blank->ind_imgext);
        $this->assertSame('someone@example.org', $set->ind_email);
        $this->assertSame('png', $set->ind_imgext);
    }

    public function test_blank_company_profiles_become_null(): void
    {
        $this->companies(3);

        DB::table('pub_dev_text')->insert([
            ['pub_dev_id' => 1, 'pub_dev_profile' => 'Founded in 1984'],
            ['pub_dev_id' => 2, 'pub_dev_profile' => "\t\t\t\t\t\t\t\t"],
            ['pub_dev_id' => 3, 'pub_dev_profile' => ''],
        ]);

        $this->migrate();

        $profiles = DB::table('pub_dev_text')->orderBy('pub_dev_id')->pluck('pub_dev_profile', 'pub_dev_id');

        $this->assertSame('Founded in 1984', $profiles[1]);
        $this->assertNull($profiles[2]);
        $this->assertNull($profiles[3]);
    }

    /**
     * Re-running it must not disturb the rows it already dealt with.
     */
    public function test_is_safe_to_run_twice(): void
    {
        $this->individuals(2);

        DB::table('individual_text')->insert([
            ['ind_id' => 1, 'ind_profile' => 'Member in Dune'],
            ['ind_id' => 2, 'ind_profile' => "\t\t"],
        ]);

        $this->migrate();
        $this->migrate();

        $profiles = DB::table('individual_text')->orderBy('ind_id')->pluck('ind_profile', 'ind_id');

        $this->assertSame('Member in Dune', $profiles[1]);
        $this->assertNull($profiles[2]);
    }
}
