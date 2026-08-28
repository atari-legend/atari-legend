<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the secondary indexes whose column list is exactly their table's primary key.
 *
 * Each is a second B-tree paid for on every insert and update, serving no read
 * the primary key does not already serve. They are leftovers of the
 * primary-key rename campaign: the index carried the old prefixed column name,
 * renameColumn moved the column and left the index behind under that name.
 *
 * This migration only ever runs against the production lineage. A database
 * built by migrate:fresh has none of these indexes -- the historical create_*
 * migrations never declared them -- so the two lineages disagree, and the
 * point of this phase is to bring production to where the migration history
 * already builds.
 *
 * That is why the index is discovered rather than named. The plan called for
 * dropping by the literal name, which is right in the sense that dropIndex(['id'])
 * would derive game_id_index and fail with 1091, but a literal name fails just
 * as hard on the lineage that never had it:
 *
 *   SQLSTATE[42000]: 1091 Can't DROP INDEX `comments_id`; check that it exists
 *
 * So the criterion is read off the schema, which is also the criterion the
 * plan's census query uses: any secondary index on exactly the primary key's
 * columns, whatever it happens to be called. Reading names out of
 * information_schema rather than deriving them is what the foreign-key
 * campaign settled on, for this same reason -- the names do not agree between
 * environments.
 *
 * The search is scoped to the ten tables the phase audited, so a redundant
 * index found anywhere else is left for whoever audits it.
 *
 * users.user_id is a unique index on id with ten inbound foreign keys; InnoDB
 * binds all ten to the primary key, so the drop is clean.
 *
 * The five remaining redundant indexes belong to tables Phase 2 renames, and
 * are dropped there in the same migration as the rename.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 1.
 */
return new class extends Migration
{
    /**
     * The ten tables audited, and the index name each carries on the
     * production lineage. The name is documentation: what gets dropped is
     * whatever the schema actually reports.
     */
    private const TABLES = [
        'comments'         => 'comments_id',
        'game'             => 'game_id',
        'game_genre'       => 'game_cat_id',
        'game_individual'  => 'game_author_id',
        'news'             => 'news_id',
        'pub_dev'          => 'pub_dev_id',
        'screenshots'      => 'screenshot_id',
        'users'            => 'user_id',
        'website'          => 'website_id',
        'website_category' => 'website_category_id',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (array_keys(self::TABLES) as $table) {
            foreach ($this->redundantIndexes($table) as $index) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            }
        }
    }

    /**
     * Deliberately empty.
     *
     * Every index this migration drops is redundant with the primary key by
     * definition, nothing at runtime reads an index name, and a migrate:fresh
     * database has none of them. Re-creating them would not restore a
     * capability; it would restore the divergence between the two lineages
     * that this phase exists to close, and on a fresh-lineage database it
     * would add indexes that were never there.
     *
     * Rolling the phase back and migrating again still round-trips, because
     * up() drops whatever it finds and finds nothing the second time.
     */
    public function down(): void
    {
    }

    /**
     * Every secondary index on this table whose column list is exactly the
     * primary key's -- the plan's census query, for one table.
     */
    private function redundantIndexes(string $table): array
    {
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
