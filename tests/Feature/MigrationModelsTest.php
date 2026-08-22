<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationModelsTest extends TestCase
{
    /**
     * Prove that no migration uses an Eloquent model.
     * Migrations must use DB::table() instead of Eloquent models, because models
     * change over time and running historical migrations with current models
     * frequently breaks (e.g., when a primary key is changed).
     */
    public function test_no_migration_uses_eloquent_models(): void
    {
        $migrations = File::files(database_path('migrations'));
        
        $violations = [];
        foreach ($migrations as $migration) {
            $content = file_get_contents($migration->getPathname());
            if (str_contains($content, 'App\Models') || preg_match('/\b([A-Z][a-zA-Z0-9_]*)::(where|create|update|all|find|updateOrCreate|firstOrCreate|insert|delete|truncate)\b/', $content)) {
                $violations[] = $migration->getFilename();
            }
        }
        
        $this->assertEmpty($violations, 'The following migrations use Eloquent models: ' . implode(', ', $violations) . '. Use DB::table() instead.');
    }
}
