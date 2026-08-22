<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests run before `npm run build` in CI, so there is no Vite manifest
        // for the layouts to resolve assets against.
        $this->withoutVite();
    }

    protected function afterRefreshingDatabase()
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $tables = \Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        $index = 1;
        foreach ($tables as $table) {
            $offset = $index * 10000; // using 10000 to be safe
            \Illuminate\Support\Facades\DB::table('sqlite_sequence')->updateOrInsert(
                ['name' => $table->name],
                ['seq' => $offset]
            );
            $index++;
        }
    }
}
