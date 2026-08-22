<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A query that joins two tables and then selects `*` unqualified is the one
 * failure mode in the primary-key rename that nothing else can see.
 *
 * Renaming a legacy key to `id` makes both tables in the join expose a column
 * called `id`. PHP keeps the last one, Eloquent hydrates the model from that
 * array, and `getKey()` returns the other table's key: no exception, no SQL
 * error, nothing in the log. Worse, those call sites never mention the old
 * column name, so no grep for `game_id` will ever surface them.
 *
 * The nine that existed were fixed by hand. This test is what stops the tenth,
 * by removing the shape rather than the instances - a `select()` with no
 * arguments is the only way the problem can be written.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase A2.
 */
class QueryConventionsTest extends TestCase
{
    public function test_no_query_selects_without_naming_its_columns(): void
    {
        $offenders = [];

        foreach ($this->phpFilesUnderApp() as $file) {
            foreach (file($file) as $number => $line) {
                if (preg_match('/->select\(\s*\)|::select\(\s*\)/', $line)) {
                    $offenders[] = $this->relativePath($file) . ':' . ($number + 1);
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", array_merge(
            ['A query must name the columns it selects. Offending call sites:'],
            $offenders,
            [
                '',
                'If the query joins another table, qualify it: select(\'own_table.*\')',
                'plus any joined column the caller reads. If it joins nothing,',
                'Model::query() says what Model::select() was being used for.',
            ]
        )));
    }

    /**
     * @return array<int, string>
     */
    private function phpFilesUnderApp(): array
    {
        $files = [];

        $tree = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($tree as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private function relativePath(string $file): string
    {
        return ltrim(str_replace(base_path(), '', $file), '/');
    }
}
