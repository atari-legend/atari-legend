<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_game.screenshot_game_id to id.
 *
 * The redundant unique index of the same name goes with it. renameColumn
 * leaves the index name behind, so the two changes belong in one migration;
 * it duplicated the primary key and served no read the primary key did not.
 *
 * The index is discovered rather than named, because the production lineage
 * and a migrate:fresh build disagree about it: the historical create_*
 * migration never declared it, so on a fresh database there is nothing to
 * drop and a literal name fails with 1091. Phase 1 makes the same move for
 * the same reason, and its docblock carries the argument. down() renames the
 * column back and leaves the index dropped, for the reason given there.
 *
 * The largest table in the phase at ~26,000 rows, and the redundant unique
 * index it drops is the one that sat on all of them.
 *
 * The separate non-unique game_id index is deliberately left alone: Phase 3a
 * binds a foreign key to it, and InnoDB will not let it be dropped afterwards.
 *
 * No model, relationship, Blade template, factory or seeder reads this column:
 * the table is model-less or reached only as a pivot, and a belongsToMany
 * derives its keys from the model names. The only files naming it are the
 * historical create_* migration and, where one exists, an older data
 * migration -- both of which run before this one in date order and are left
 * alone.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->redundantIndexes('screenshot_game') as $index) {
            Schema::table('screenshot_game', fn (Blueprint $t) => $t->dropIndex($index));
        }

        Schema::table('screenshot_game', fn (Blueprint $t) => $t->renameColumn('screenshot_game_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('screenshot_game', fn (Blueprint $t) => $t->renameColumn('id', 'screenshot_game_id'));
    }

    /**
     * Every secondary index on this table whose column list is exactly the
     * primary key's -- the schema-consistency plan's census query, for one
     * table. Empty on a migrate:fresh database, which never had one.
     */
    private function redundantIndexes(string $table): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return [];
        }

        $byName = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->orderBy('SEQ_IN_INDEX')
            ->get(['INDEX_NAME', 'COLUMN_NAME'])
            ->groupBy('INDEX_NAME')
            ->map(fn ($rows) => $rows->pluck('COLUMN_NAME')->implode(','));

        $primary = $byName->get('PRIMARY');

        return $byName
            ->forget('PRIMARY')
            ->filter(fn ($columns) => $columns === $primary)
            ->keys()
            ->all();
    }
};
