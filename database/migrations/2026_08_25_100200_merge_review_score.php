<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold review_score into review_main and drop it.
 *
 * Unlike the article and interview merges, review_main already carries its own
 * text and date; what moves here is four integers. They land **nullable**,
 * which is a deliberate change of meaning and not an oversight: the public
 * page already guards the score block with @isset and the admin controller
 * already coped with a missing score row, so "this review has no score" is a
 * state the code supports. Every one of the 126 reviews in production has a
 * score, so the merge itself produces no NULLs.
 *
 * That nullability costs this migration its free integrity assertion -- there
 * is no nullable(false)->change() step for a failed backfill to abort on -- so
 * the explicit $expected/$moved comparison below is the only thing standing
 * between a partial backfill and a dropped table. It is not optional.
 *
 * The down() declares the four columns **nullable**, which drifts from the
 * production schema (2025_12_30_113644_review_constraints made them INT NOT
 * NULL). The alternative drifts worse: a faithful NOT NULL down() would abort
 * its insertUsing under strict mode on any review saved without scores after
 * this deploy, which is exactly the state the up() just made legal. A schema
 * flag that disagrees with history is recoverable; a rollback that refuses to
 * run is not.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 3.
 */
return new class extends Migration
{
    private const COLUMNS = ['review_graphics', 'review_sound', 'review_gameplay', 'review_overall'];

    public function up(): void
    {
        $duplicates = DB::table('review_score')
            ->select('review_id')->groupBy('review_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("review_score holds {$duplicates} reviews with more than one row.");
        }

        $expected = DB::table('review_score')->count();

        Schema::table('review_main', function (Blueprint $t) {
            $t->integer('review_graphics')->nullable()->after('review_edit');
            $t->integer('review_sound')->nullable()->after('review_graphics');
            $t->integer('review_gameplay')->nullable()->after('review_sound');
            $t->integer('review_overall')->nullable()->after('review_gameplay');
        });

        foreach (self::COLUMNS as $column) {
            DB::table('review_main')->update([
                $column => DB::raw("(SELECT t.`{$column}` FROM `review_score` t WHERE t.`review_id` = `review_main`.`id`)"),
            ]);
        }

        $moved = DB::table('review_main')->whereNotNull('review_graphics')->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} review_score rows; refusing to drop the table.");
        }

        Schema::drop('review_score');
    }

    public function down(): void
    {
        Schema::create('review_score', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('review_id');
            // Nullable, unlike production -- see the docblock.
            $t->integer('review_graphics')->nullable();
            $t->integer('review_sound')->nullable();
            $t->integer('review_gameplay')->nullable();
            $t->integer('review_overall')->nullable();
            $t->foreign('review_id')->references('id')->on('review_main')->cascadeOnDelete();
        });

        DB::table('review_score')->insertUsing(
            ['review_id', ...self::COLUMNS],
            DB::table('review_main')->select('id', ...self::COLUMNS)
        );

        Schema::table('review_main', function (Blueprint $t) {
            $t->dropColumn(self::COLUMNS);
        });
    }
};
