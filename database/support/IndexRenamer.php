<?php

namespace Database\Support;

use Illuminate\Support\Facades\DB;

/**
 * Rename a table's own secondary indexes and foreign-key constraints to the
 * name Laravel would derive today -- independent of any table or column
 * rename, and independent of TableRenamer, which only ever rewrites a name
 * that already began with the table's *old* name.
 *
 * Two different operations, because MariaDB 10.11 can rename an index in
 * place but not a constraint (docs/plans/2026-08-23-foreign-key-rename.md,
 * "The trap: constraint and index names do not follow the column"):
 *
 * - **renameIndexes()** is `ALTER TABLE ... RENAME KEY ... TO ...`. Cheap,
 *   metadata-only, and safe to point at an index backing a live foreign key
 *   -- renaming it does not touch the constraint that uses it, which is
 *   exactly why the two need separate maps below.
 * - **renameForeignKeys()** drops the constraint and re-adds it under the
 *   new name, against the column, reference and `ON UPDATE`/`ON DELETE`
 *   rule supplied by the caller. It creates no second index: if a
 *   suitably-named index already exists -- typically because
 *   renameIndexes() just made one -- MariaDB reuses it.
 *
 * renameIndexes() finds the index to rename **by its column list, not by an
 * assumed old name**, and only issues the `RENAME KEY` if the name it finds
 * differs from the target. That is not a style choice: this schema has two
 * independent histories for the same set of tables. Every table this class
 * touches was originally created by a migration this campaign never edited,
 * and that migration ran twice, years apart, in two different ways --
 * `php artisan migrate` against production and every long-lived dev database
 * (in the deployed order, once each, going back to 2020), and `php artisan
 * migrate:fresh` against any environment rebuilt from scratch today. Where a
 * migration explicitly names an index, both give the same name. Where an old
 * migration only declared a bare `$table->index('col')` and Laravel filled in
 * the name, the two histories can disagree regardless -- measured 2026-09-06,
 * `game_developer.company_id`'s index is `game_developer_pub_dev_id_index` on
 * a fresh install and the bare `pub_dev_id` on the long-lived dev database
 * that mirrors production. `foreign-key-rename` hit this first, for table
 * renames rather than bare index names, and drew the same conclusion: read
 * the current name at run time, never assume it.
 *
 * A migration using both methods should call renameIndexes() first, so that
 * by the time renameForeignKeys() adds the new constraint, the index it will
 * reuse already carries the matching name. Reversing runs the opposite
 * order.
 *
 * See docs/plans/2026-09-06-index-name-consistency.md.
 */
class IndexRenamer
{
    /**
     * @param  array<string, list<array{0: string, 1: list<string>}>>  $indexes
     *                                                                           table => list of [target index name, columns]
     */
    public static function renameIndexes(array $indexes): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($indexes as $table => $entries) {
            foreach ($entries as [$to, $columns]) {
                $current = self::findIndex($table, $columns);

                if ($current !== null && $current !== $to) {
                    DB::statement("ALTER TABLE `{$table}` RENAME KEY `{$current}` TO `{$to}`");
                }
            }
        }
    }

    /**
     * Reverse renameIndexes(), restoring the name each entry was measured
     * under on the long-lived dev database that mirrors production. On an
     * environment built by `migrate:fresh`, where a migration already
     * declared the eventual name, this restores the pre-rename production
     * name rather than that migration's own -- a cosmetic difference with no
     * runtime effect, on an environment this class does not need to make
     * byte-for-byte reversible, only correct once migrated forward again.
     *
     * @param  array<string, list<array{0: string, 1: list<string>, 2: string}>>  $indexes
     *                                                                                      table => list of [target index name, columns, original name]
     */
    public static function reverseIndexes(array $indexes): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (array_reverse($indexes, true) as $table => $entries) {
            foreach (array_reverse($entries) as [$to, $columns, $from]) {
                $current = self::findIndex($table, $columns);

                if ($current !== null && $current !== $from) {
                    DB::statement("ALTER TABLE `{$table}` RENAME KEY `{$current}` TO `{$from}`");
                }
            }
        }
    }

    /**
     * The name of the index covering exactly this column list, in order, or
     * null if none does. This schema has one table with two indexes on the
     * same columns (`games.slug`, one plain and one unique) -- neither is
     * ever a target of a rename, but ORDER BY NON_UNIQUE makes the match
     * deterministic regardless.
     *
     * @param  list<string>  $columns
     */
    private static function findIndex(string $table, array $columns): ?string
    {
        $schema = DB::getDatabaseName();

        $rows = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->orderBy('SEQ_IN_INDEX')
            ->orderBy('NON_UNIQUE')
            ->get(['INDEX_NAME', 'COLUMN_NAME']);

        foreach ($rows->groupBy('INDEX_NAME') as $name => $indexColumns) {
            if ($indexColumns->pluck('COLUMN_NAME')->all() === $columns) {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string}>  $constraints
     *                                                                                                                            [table, old constraint name, new constraint name, column, referenced table, referenced column, ON UPDATE rule, ON DELETE rule]
     */
    public static function renameForeignKeys(array $constraints): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach ($constraints as [$table, $from, $to, $column, $refTable, $refColumn, $onUpdate, $onDelete]) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$from}`");
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$to}`"
                . " FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`)"
                . " ON DELETE {$onDelete} ON UPDATE {$onUpdate}"
            );
        }
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: string, 7: string}>  $constraints
     */
    public static function reverseForeignKeys(array $constraints): void
    {
        $reversed = array_map(
            fn (array $c) => [$c[0], $c[2], $c[1], $c[3], $c[4], $c[5], $c[6], $c[7]],
            array_reverse($constraints)
        );

        self::renameForeignKeys($reversed);
    }
}
