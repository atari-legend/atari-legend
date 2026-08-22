<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A migration that drives an Eloquent model is written against the schema of
 * the day it ran, but resolves the model as it is *today*. Rename a primary
 * key and `migrate:fresh` starts failing inside a migration from 2020 that
 * has not changed in years - `ArticleText::each()` chunks by the model's key,
 * so it looks for `article_text.id` before that column exists.
 *
 * `artisan test` cannot see this class of breakage on its own: several of
 * these migrations are guarded by `!== 'sqlite'`, so PHPUnit skips them
 * wholesale and a fully green suite can sit on top of a `migrate:fresh` that
 * is broken on MariaDB. This test is the cheap half of the guard; the CI
 * MariaDB migration job is the other half.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, harness changes 2 and 3.
 */
class MigrationModelsTest extends TestCase
{
    public function test_no_migration_uses_eloquent_models(): void
    {
        $models = $this->modelClassNames();

        $violations = [];

        foreach (File::files(database_path('migrations')) as $migration) {
            $content = file_get_contents($migration->getPathname());

            // Catches both `use App\Models\Game;` and a fully qualified
            // `\App\Models\Game::find(...)` in the body.
            if (str_contains($content, 'App\Models')) {
                $violations[] = $migration->getFilename() . ' (references App\Models)';

                continue;
            }

            // And a bare `Game::find(...)` left behind when only the import was
            // removed - which is how insert_sndh_2026 came to call a class that
            // no longer resolved. Matching the real model names rather than any
            // capitalised token keeps Schema::create() and DB::table() out of
            // it; there are 199 of those, and they are exactly what a migration
            // is supposed to use.
            foreach ($models as $model) {
                if (preg_match('/(?<![\w\\\\])' . preg_quote($model, '/') . '::/', $content)) {
                    $violations[] = $migration->getFilename() . " (calls {$model}::)";

                    break;
                }
            }
        }

        $this->assertSame([], $violations, implode("\n", array_merge(
            ['A migration must not use an Eloquent model. Offending files:'],
            $violations,
            ['', 'Use DB::table() with the column names the schema had at the time.']
        )));
    }

    /**
     * @return array<int, string>
     */
    private function modelClassNames(): array
    {
        return array_map(
            fn ($file) => $file->getFilenameWithoutExtension(),
            File::files(app_path('Models'))
        );
    }
}
