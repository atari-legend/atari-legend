<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests run before `npm run build` in CI, so there is no Vite manifest
        // for the layouts to resolve assets against.
        $this->withoutVite();

        // After parent::setUp(), because that is what runs setUpTraits() and so
        // RefreshDatabase: by this point the schema is fresh and the per-test
        // transaction is open, which is where the offsets below belong.
        //
        // Deliberately *not* named afterRefreshingDatabase(). RefreshDatabase
        // declares its own empty version of that hook, and a trait method used
        // on the test class beats a method inherited from this base class - so
        // an override here would never be called.
        $this->giveEachTableItsOwnIdRange();
    }

    /**
     * Start every table's auto-increment at a different number.
     *
     * Fixtures otherwise make a parent and its child both id 1, so a key read
     * from the wrong table returns the right number by coincidence and the
     * suite cannot see the bug. Distinct ranges make that arithmetically
     * visible. See docs/plans/2026-08-17-primary-key-rename.md.
     */
    private function giveEachTableItsOwnIdRange(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        // Only AUTOINCREMENT tables consult sqlite_sequence; a row for any
        // other table is inert, so skip them and keep the per-test cost down.
        $tables = DB::select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND sql LIKE '%AUTOINCREMENT%'"
        );

        foreach ($tables as $index => $table) {
            // updateOrInsert, not insert: migrations leave rows in
            // sqlite_sequence already, and it carries no unique constraint, so
            // a plain insert would silently duplicate and the offset be lost.
            DB::table('sqlite_sequence')->updateOrInsert(
                ['name' => $table->name],
                ['seq'  => ($index + 1) * 1000]
            );
        }
    }
}
