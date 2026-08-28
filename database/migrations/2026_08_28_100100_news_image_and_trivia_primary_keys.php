<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Give news_image and trivia the primary key they never had.
 *
 * On the production lineage both carry an `id int(11) NOT NULL AUTO_INCREMENT`
 * column and no primary key at all -- only a UNIQUE KEY still named for the
 * column before the primary-key campaign renamed it (news_image_id,
 * trivia_id).
 *
 * DESCRIBE is not the check: MySQL labels a UNIQUE NOT NULL index PRI when a
 * table has no primary key, so DESCRIBE already reports `id` as PRI on both.
 * Only information_schema and SHOW CREATE TABLE show the real state, which is
 * why the guard below asks information_schema for an index literally called
 * PRIMARY.
 *
 * The guard is also what makes this runnable on a migrate:fresh database,
 * where both tables already have PRIMARY KEY (id) and there is nothing to do.
 * The two lineages disagree, and closing that gap is the point of the phase.
 *
 * Order matters on the lineage that does need the work. InnoDB requires the
 * AUTO_INCREMENT column to lead some key at every point, so the primary key is
 * added before the stale unique index is dropped. The other order fails:
 *
 *   ERROR 1075 (42000): Incorrect table definition; there can be only one auto
 *   column and it must be defined as a key
 *
 * Raw statements rather than the Blueprint, because the two halves have to be
 * ordered explicitly and Blueprint batches them into one ALTER.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 1.
 */
return new class extends Migration
{
    private const TABLES = ['news_image', 'trivia'];

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (self::TABLES as $table) {
            if ($this->indexes($table)->contains('PRIMARY')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` ADD PRIMARY KEY (`id`)");

            foreach ($this->indexes($table) as $index) {
                if ($index !== 'PRIMARY') {
                    DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
                }
            }
        }
    }

    /**
     * Deliberately empty, for the reason given on the migration that drops the
     * redundant indexes: a migrate:fresh database already has these primary
     * keys, so dropping them on the way back would take a database further
     * from the state the migration history builds rather than nearer it.
     *
     * Re-running up() after a rollback finds the primary key in place and does
     * nothing, so the phase still round-trips.
     */
    public function down(): void
    {
    }

    /**
     * The names of every index on the table, PRIMARY included.
     */
    private function indexes(string $table): \Illuminate\Support\Collection
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->distinct()
            ->pluck('INDEX_NAME');
    }
};
