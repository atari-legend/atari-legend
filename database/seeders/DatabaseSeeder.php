<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application with the end-to-end fixture: a browsable site, and
     * an administrator to sign in as.
     *
     * E2ESeeder refuses to run in production and leaves an existing dataset
     * alone, so `--seed` is safe wherever it can be typed.
     */
    public function run(): void
    {
        $this->call(E2ESeeder::class);
    }
}
