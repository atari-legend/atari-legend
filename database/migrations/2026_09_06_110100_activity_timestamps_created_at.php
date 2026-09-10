<?php

use Database\Support\UnixTimestampColumnConverter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Eight columns that record when their row was created -- five int(11) unix
 * timestamps, three varchar(32) ones -- to DATETIME, all named created_at so
 * that Eloquent maintains them and no writer has to.
 *
 * comments is the exception in kind: its column was rewritten on every edit,
 * so the date the site showed on an edited comment was the date of the edit.
 * It gets both Laravel columns, and updated_at is backfilled from created_at
 * because today's value already holds the time of the last edit. From here
 * the edit paths assign neither and Eloquent keeps them apart.
 *
 * The index on changelogs.timestamp is dropped before the conversion and
 * recreated on the new column after it. MariaDB would drop a single-column
 * index with its column, but SQLite refuses to drop an indexed column at all,
 * and migrate:fresh runs 2026_08_09_000000_change_log_indexes on both
 * engines.
 *
 * The index is found by its column rather than by name, the rule IndexRenamer
 * states and for the same reason: the two engines disagree on what it is
 * called. 2026_08_09_000000_change_log_indexes created it as
 * change_log_timestamp_index, TableRenamer rewrote that to
 * changelogs_timestamp_index when the table was pluralised, and
 * TableRenamer skips SQLite -- so a test database still carries the old stem.
 * Both directions recreate the index under the name Laravel derives today,
 * which is the one name that cannot be a surprise later.
 *
 * The DEFAULT 0 on links, link_submissions and news_submissions does not
 * survive -- a DATETIME cannot carry it. Under STRICT_TRANS_TABLES an insert
 * that omits one of those columns now fails instead of storing 0; every
 * writer sets it, or lets Eloquent set it. down() restores the default.
 *
 * See docs/plans/2026-09-06-timestamp-type-consistency.md, Unit 2.
 */
return new class extends Migration
{
    /**
     * table => [source column, source type, source length, nullable, default].
     */
    private const COLUMNS = [
        'changelogs'       => ['timestamp', 'integer', null, false, null],
        'dumps'            => ['date', 'integer', null, true, null],
        'links'            => ['date', 'integer', null, false, 0],
        'link_submissions' => ['date', 'integer', null, false, 0],
        'news_submissions' => ['date', 'integer', null, false, 0],
        'game_submissions' => ['timestamp', 'string', 32, true, null],
        'comments'         => ['timestamp', 'string', 32, true, null],
        'andreas'          => ['timestamp', 'string', 32, true, null],
    ];

    public function up(): void
    {
        $this->dropIndexOn('changelogs', 'timestamp');

        foreach (self::COLUMNS as $table => [$from, $sourceType, , $nullable]) {
            UnixTimestampColumnConverter::up($table, $from, 'created_at', $sourceType, $nullable);
        }

        Schema::table('changelogs', fn (Blueprint $t) => $t->index('created_at'));

        Schema::table('comments', fn (Blueprint $t) => $t->dateTime('updated_at')->nullable());
        DB::statement('UPDATE `comments` SET `updated_at` = `created_at`');
    }

    public function down(): void
    {
        Schema::table('comments', fn (Blueprint $t) => $t->dropColumn('updated_at'));

        $this->dropIndexOn('changelogs', 'created_at');

        foreach (array_reverse(self::COLUMNS, true) as $table => [$from, $sourceType, $length, $nullable, $default]) {
            UnixTimestampColumnConverter::down(
                $table,
                'created_at',
                $from,
                $sourceType,
                $length,
                $nullable,
                $default,
            );
        }

        Schema::table('changelogs', fn (Blueprint $t) => $t->index('timestamp'));
    }

    /**
     * Drop whatever index covers exactly $column, under the name it actually
     * carries on this connection.
     */
    private function dropIndexOn(string $table, string $column): void
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === [$column]) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index['name']));
            }
        }
    }
};
