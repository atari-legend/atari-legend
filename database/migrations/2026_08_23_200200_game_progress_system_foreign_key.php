<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game.progress_system_id to game_progress_system_id.
 *
 * The table it points at is game_progress_system, and the column's own
 * comment has been saying so all along. Game::progressSystem() is renamed to
 * gameProgressSystem() in the same change, because belongsTo derives its key
 * from the method name and ->progressSystem is three hits.
 *
 * See docs/plans/2026-08-23-foreign-key-rename.md, Phase C.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameForeignKeyColumn('game', 'progress_system_id', 'game_progress_system_id');
    }

    public function down(): void
    {
        $this->renameForeignKeyColumn('game', 'game_progress_system_id', 'progress_system_id');
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
