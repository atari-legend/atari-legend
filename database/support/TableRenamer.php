<?php

namespace Database\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename a table, and the indexes and constraints that do not follow it.
 *
 * Schema::rename() alone is not enough, and the half it misses is the half
 * that bites later. InnoDB rewrites the referenced table name in every
 * foreign key *pointing at* a renamed table, so the children look after
 * themselves. The renamed table's own indexes and constraints keep their old
 * names: `games` would still carry `game_slug_unique`. That is cosmetic right
 * up until a later migration writes dropUnique(['slug']) or
 * dropForeign(['port_id']), where Laravel derives the name from the *new*
 * table, does not find it, and fails with SQLSTATE 42000 / 1091.
 *
 * 2026_08_25_100300_rename_main_tables established the shape; this class is
 * that migration's private rename() extracted, because the pluralisation
 * campaign of docs/plans/2026-08-29-plural-table-rename.md needs it in seven
 * migrations rather than one. It is deliberately free of models and of
 * anything that could change meaning later: it reads names out of
 * information_schema and rewrites a prefix.
 *
 * Two rules it applies that the original did not have to:
 *
 * - **Only a Laravel-derived name is rewritten**, matched as
 *   `{table}_{columns}_{index|unique|foreign|primary}`. A blind prefix swap
 *   would rewrite this schema's legacy indexes too, and those are named for
 *   their *column*, not their table: `game` carries an index called
 *   `game_progress_system_id` and one called `game_series_id`, and turning
 *   them into `games_progress_system_id` would name them after neither the
 *   column nor a convention. They are left, exactly as the four legacy names
 *   the merge campaign left, and for the same reason: the rename does not
 *   make them worse. The `*_ibfk_*` constraints MariaDB generated are left by
 *   the same rule.
 *
 * - **A rewritten name longer than 64 characters is skipped**, because that
 *   is MariaDB's identifier limit and the ALTER would fail. It happens once
 *   in the campaign, on
 *   game_release_tos_version_incompatibility_game_release_id_foreign, whose
 *   pluralised form is 66. Nothing is lost that was reachable: Laravel would
 *   derive that same 66-character name for a later dropForeign(), so no
 *   dropForeign() on that column can work whatever this class does.
 *
 * SQLite is skipped for the raw statements -- it names neither indexes nor
 * constraints after the table -- and guarded on 'not sqlite' rather than
 * '=== mysql', which would silently no-op because the driver is 'mariadb'.
 */
class TableRenamer
{
    /**
     * MariaDB's identifier limit.
     */
    private const MAX_IDENTIFIER = 64;

    /**
     * The suffixes Laravel appends to a name it derives from a table.
     */
    private const DERIVED_SUFFIXES = ['index', 'unique', 'foreign', 'primary'];

    /**
     * Rename every table in the map, old name => new name.
     *
     * @param array<string, string> $tables
     */
    public static function rename(array $tables): void
    {
        foreach ($tables as $from => $to) {
            self::renameOne($from, $to);
        }
    }

    /**
     * Reverse a map, for a migration's down(): new name => old name, applied
     * in reverse order so the statements undo in the order they were made.
     *
     * @param array<string, string> $tables
     */
    public static function reverse(array $tables): void
    {
        foreach (array_reverse($tables, true) as $to => $from) {
            self::renameOne($from, $to);
        }
    }

    private static function renameOne(string $from, string $to): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::rename($from, $to);

            return;
        }

        $schema = DB::getDatabaseName();

        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->pluck('INDEX_NAME')
            ->unique();

        // The rules are read rather than assumed: ON DELETE is not uniform
        // across this schema, and dropping a constraint to rename it would
        // otherwise put back a different one.
        $rules = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->get(['CONSTRAINT_NAME', 'UPDATE_RULE', 'DELETE_RULE'])
            ->keyBy('CONSTRAINT_NAME');

        $keys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->orderBy('ORDINAL_POSITION')
            ->get(['CONSTRAINT_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
            ->groupBy('CONSTRAINT_NAME');

        Schema::rename($from, $to);

        foreach ($indexes as $index) {
            if ($renamed = self::rewrite($index, $from, $to)) {
                DB::statement("ALTER TABLE `{$to}` RENAME KEY `{$index}` TO `{$renamed}`");
            }
        }

        foreach ($keys as $constraint => $columns) {
            if (! $renamed = self::rewrite($constraint, $from, $to)) {
                continue;
            }

            $rule = $rules[$constraint];
            $local = $columns->pluck('COLUMN_NAME')->map(fn ($c) => "`{$c}`")->implode(', ');
            $foreign = $columns->pluck('REFERENCED_COLUMN_NAME')->map(fn ($c) => "`{$c}`")->implode(', ');

            DB::statement("ALTER TABLE `{$to}` DROP FOREIGN KEY `{$constraint}`");
            DB::statement(
                "ALTER TABLE `{$to}` ADD CONSTRAINT `{$renamed}`"
                . " FOREIGN KEY ({$local})"
                . " REFERENCES `{$columns->first()->REFERENCED_TABLE_NAME}` ({$foreign})"
                . " ON DELETE {$rule->DELETE_RULE} ON UPDATE {$rule->UPDATE_RULE}"
            );
        }
    }

    /**
     * The new name for an index or constraint, or null if it is not one
     * Laravel derived from the old table name and so must be left alone.
     */
    private static function rewrite(string $name, string $from, string $to): ?string
    {
        $suffixes = implode('|', self::DERIVED_SUFFIXES);

        if (! preg_match('/^' . preg_quote($from, '/') . '_.+_(' . $suffixes . ')$/', $name)) {
            return null;
        }

        $renamed = $to . substr($name, strlen($from));

        return strlen($renamed) <= self::MAX_IDENTIFIER ? $renamed : null;
    }
}
