<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold pub_dev_text into pub_dev and drop it.
 *
 * The same shape as the individuals merge before it and better behaved: 1,387
 * companies, 1,185 text rows, 202 companies with none and **no company with
 * more than one**, re-verified. So this one keeps the plain duplicate check
 * the article and interview merges use, and needs no ORDER BY ... LIMIT 1.
 *
 * Dropping the table also deletes a primary key column literally called
 * `pub_dev_text`, and with it one of the 36 legacy prefixed primary keys the
 * key campaign recorded as outstanding, plus the FIXME on
 * PublisherDeveloperText that named it. That rename is retired rather than
 * executed - the two campaigns cancel here instead of colliding.
 *
 * As in Phase 5 the columns land nullable, so the explicit $expected/$moved
 * count is the only integrity assertion, and the down() must declare its
 * columns ->nullable(): 1,374 of the 1,387 companies have a NULL in at least
 * one of them, so a NOT NULL down() would abort on the first row it tried.
 * The down() also recreates one row per company, 1,387 where production has
 * 1,185 - the same deliberate drift as Phase 5, for the same reason.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 6.
 */
return new class extends Migration
{
    private const COLUMNS = ['pub_dev_profile', 'pub_dev_imgext'];

    public function up(): void
    {
        $duplicates = DB::table('pub_dev_text')
            ->select('pub_dev_id')->groupBy('pub_dev_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("pub_dev_text holds {$duplicates} companies with more than one row.");
        }

        $expected = DB::table('pub_dev_text')->distinct()->count('pub_dev_id');

        Schema::table('pub_dev', function (Blueprint $t) {
            $t->mediumText('pub_dev_profile')->nullable()->after('pub_dev_name');
            $t->string('pub_dev_imgext', 50)->nullable()->after('pub_dev_profile');
        });

        foreach (self::COLUMNS as $column) {
            DB::table('pub_dev')->update([
                $column => DB::raw("(SELECT t.`{$column}` FROM `pub_dev_text` t WHERE t.`pub_dev_id` = `pub_dev`.`id`)"),
            ]);
        }

        // Counted over the companies that had a row at all: 202 had none and
        // the columns are nullable, so what is checked is that every child row
        // found a home, not that every parent gained data.
        $moved = DB::table('pub_dev')
            ->whereIn('id', DB::table('pub_dev_text')->distinct()->pluck('pub_dev_id'))
            ->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} pub_dev_text rows; refusing to drop the table.");
        }

        Schema::drop('pub_dev_text');
    }

    public function down(): void
    {
        Schema::create('pub_dev_text', function (Blueprint $t) {
            // The legacy primary key is a column named after the table, and a
            // signed int(11). Reproduced exactly, FIXME and all, because a
            // down() that quietly improves the schema is a down() that does
            // not restore it.
            $t->integer('pub_dev_text', true);
            $t->integer('pub_dev_id')->nullable();
            $t->mediumText('pub_dev_profile')->nullable();
            $t->string('pub_dev_imgext', 50)->nullable();
            $t->foreign('pub_dev_id')->references('id')->on('pub_dev')->cascadeOnDelete();
        });

        DB::table('pub_dev_text')->insertUsing(
            ['pub_dev_id', ...self::COLUMNS],
            DB::table('pub_dev')->select('id', ...self::COLUMNS)
        );

        Schema::table('pub_dev', function (Blueprint $t) {
            $t->dropColumn(self::COLUMNS);
        });
    }
};
