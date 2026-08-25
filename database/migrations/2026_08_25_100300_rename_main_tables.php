<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the four _main tables to Laravel's plural convention.
 *
 * article_main -> articles, interview_main -> interviews,
 * review_main -> reviews, screenshot_main -> screenshots.
 *
 * Schema::rename() alone is not enough, and the half it misses is the half
 * that bites later. InnoDB rewrites the referenced table name in every foreign
 * key *pointing at* a renamed table, so the children look after themselves.
 * The renamed table's own indexes and constraints keep their old names:
 * `articles` would still carry `article_main_user_id_foreign`. That is
 * cosmetic right up until a later migration writes dropForeign(['user_id']),
 * where Laravel derives `articles_user_id_foreign`, does not find it, and
 * fails with SQLSTATE 42000 / 1091 - the same trap the foreign key campaign
 * documented for renamed columns, one level up.
 *
 * So the names are read out of information_schema and rewritten, exactly as
 * 2026_08_23_200500_individual_foreign_keys does, rather than derived. Three
 * legacy index names are deliberately left alone: `user_id` on interviews and
 * reviews, which back their foreign keys under a pre-Laravel name, and
 * `screenshot_id` on screenshots, which is redundant with the primary key it
 * sits on. None contains a table name, so the rename does not make any of them
 * worse, and dropping a redundant index is a different change from renaming a
 * table.
 *
 * SQLite is skipped for the raw statements - it names neither indexes nor
 * constraints after the table - and guarded on 'not sqlite' rather than
 * '=== mysql', which would silently no-op because the driver is 'mariadb'.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 4.
 */
return new class extends Migration
{
    private const TABLES = [
        'article_main'    => 'articles',
        'interview_main'  => 'interviews',
        'review_main'     => 'reviews',
        'screenshot_main' => 'screenshots',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $from => $to) {
            $this->rename($from, $to);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES, true) as $to => $from) {
            $this->rename($from, $to);
        }
    }

    /**
     * Rename a table, and the indexes and constraints that do not follow it.
     */
    private function rename(string $from, string $to): void
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

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->distinct()
            ->pluck('CONSTRAINT_NAME')
            ->unique();

        // The rules are read rather than assumed: ON DELETE is not uniform
        // across this schema, and dropping a constraint to rename it would
        // otherwise put back a different one.
        $rules = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->get(['CONSTRAINT_NAME', 'UPDATE_RULE', 'DELETE_RULE'])
            ->keyBy('CONSTRAINT_NAME');

        $columns = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $schema)
            ->where('TABLE_NAME', $from)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get(['CONSTRAINT_NAME', 'COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
            ->keyBy('CONSTRAINT_NAME');

        Schema::rename($from, $to);

        foreach ($indexes as $index) {
            $renamed = str_replace($from, $to, $index);

            if ($renamed !== $index) {
                DB::statement("ALTER TABLE `{$to}` RENAME KEY `{$index}` TO `{$renamed}`");
            }
        }

        foreach ($constraints as $constraint) {
            $renamed = str_replace($from, $to, $constraint);

            if ($renamed === $constraint) {
                continue;
            }

            $column = $columns[$constraint];
            $rule = $rules[$constraint];

            DB::statement("ALTER TABLE `{$to}` DROP FOREIGN KEY `{$constraint}`");
            DB::statement(
                "ALTER TABLE `{$to}` ADD CONSTRAINT `{$renamed}`"
                . " FOREIGN KEY (`{$column->COLUMN_NAME}`)"
                . " REFERENCES `{$column->REFERENCED_TABLE_NAME}` (`{$column->REFERENCED_COLUMN_NAME}`)"
                . " ON DELETE {$rule->DELETE_RULE} ON UPDATE {$rule->UPDATE_RULE}"
            );
        }
    }
};
