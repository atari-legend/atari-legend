<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename crew_individual.individual_nicks_id to individual_nick_id.
 *
 * The plural was the anomaly: the table is individual_nicks, and the rule the
 * campaign follows singularises the referenced table name.
 *
 * This one points at a legacy primary key and that is fine. After the rename
 * the constraint reads FOREIGN KEY (individual_nick_id) REFERENCES
 * individual_nicks (individual_nicks_id), which looks odd and is correct: only
 * the child column moves here. The two campaigns are independent -- whenever
 * individual_nicks_id -> id happens on the parent it does not touch this
 * column, and vice versa.
 *
 * It is also the only rename in the campaign that edits no PHP at all: no
 * relation, no $fillable, no query names this column anywhere in app/.
 *
 * See docs/plans/2026-08-23-foreign-key-rename.md, Phase C.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameForeignKeyColumn('crew_individual', 'individual_nicks_id', 'individual_nick_id');
    }

    public function down(): void
    {
        $this->renameForeignKeyColumn('crew_individual', 'individual_nick_id', 'individual_nicks_id');
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
