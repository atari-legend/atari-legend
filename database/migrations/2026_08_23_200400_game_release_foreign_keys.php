<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename release_id to game_release_id, on all ten tables that carry it.
 *
 * The table is game_release, so this is the instructed direction: the schema
 * is what the code bends to. Nine of the ten are pivots named game_release_*;
 * the tenth is `media`, which is the one a glob over that prefix would miss
 * and the one with a real model behind it.
 *
 * Every one of these columns is loud when it goes wrong -- a stale name is a
 * 1054 on read and on write, not a silently dropped field -- which is why the
 * largest rename in the campaign is not the last one.
 *
 * The list is the answer to this query, not a list maintained by hand:
 *
 *   SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
 *   WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
 *     AND COLUMN_NAME = 'release_id';
 *
 * See docs/plans/2026-08-23-foreign-key-rename.md, Phase C.
 */
return new class extends Migration
{
    private const TABLES = [
        'game_release_copy_protection',
        'game_release_disk_protection',
        'game_release_emulator_incompatibility',
        'game_release_language',
        'game_release_memory_enhanced',
        'game_release_memory_incompatible',
        'game_release_memory_minimum',
        'game_release_tos_version_incompatibility',
        'game_release_trainer_option',
        'media',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->renameForeignKeyColumn($table, 'release_id', 'game_release_id');
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            $this->renameForeignKeyColumn($table, 'game_release_id', 'release_id');
        }
    }

    /**
     * Rename a foreign key column, and the index and the constraint that do
     * not follow it.
     *
     * MariaDB leaves both named for the old column after renameColumn, which
     * is cosmetic until a later migration writes dropForeign() or dropIndex()
     * and Laravel derives the name from the *new* column: SQLSTATE 42000,
     * 1091. Both names are read out of information_schema rather than derived,
     * because they do not agree between environments -- production carries
     * legacy names (an index literally called `release_id`) while a database
     * built by migrate:fresh carries whatever the historical migration called
     * them (`game_developer_dev_pub_id_index`). The ON DELETE and ON UPDATE
     * rules are read for the same reason: they are not uniform in this schema.
     *
     * SQLite rewrites the foreign key clause itself and names neither, so the
     * raw statements are skipped there -- guarded on 'not sqlite', never on
     * '=== mysql', which silently no-ops because the driver is 'mariadb'.
     */
    private function renameForeignKeyColumn(string $table, string $from, string $to): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table($table, fn (Blueprint $t) => $t->renameColumn($from, $to));

            return;
        }

        $schema = DB::getDatabaseName();

        $constraint = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $from)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->first(['CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME']);

        $rules = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint->CONSTRAINT_NAME)
            ->first(['UPDATE_RULE', 'DELETE_RULE']);

        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $from)
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->pluck('INDEX_NAME')
            ->unique();

        Schema::table($table, fn (Blueprint $t) => $t->renameColumn($from, $to));

        foreach ($indexes as $index) {
            if (str_replace($from, $to, $index) !== $index) {
                DB::statement("ALTER TABLE `{$table}` RENAME KEY `{$index}` TO `" . str_replace($from, $to, $index) . '`');
            }
        }

        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `" . str_replace($from, $to, $constraint->CONSTRAINT_NAME) . '`'
            . " FOREIGN KEY (`{$to}`) REFERENCES `{$constraint->REFERENCED_TABLE_NAME}` (`{$constraint->REFERENCED_COLUMN_NAME}`)"
            . " ON DELETE {$rules->DELETE_RULE} ON UPDATE {$rules->UPDATE_RULE}"
        );
    }
};
