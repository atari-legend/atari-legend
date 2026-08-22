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
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        $tables = \Illuminate\Support\Facades\DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        
        $index = 1;
        $seqs = [];
        foreach ($tables as $table) {
            $seqs[] = ['name' => $table->name, 'seq' => $index * 10000];
            $index++;
        }
        
        \Illuminate\Support\Facades\DB::table('sqlite_sequence')->delete();
        \Illuminate\Support\Facades\DB::table('sqlite_sequence')->insert($seqs);
    }
}
