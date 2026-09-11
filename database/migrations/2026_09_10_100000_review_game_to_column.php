<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Move the review's game out of game_review and onto reviews.game_id.
 *
 * The pivot holds one row per review and the application cannot express more
 * than one game per review: every writer sends a single game and every reader
 * unwraps the single-element collection to reach it. Without a unique index
 * on review_id, a second row would change which game a review renders with
 * nothing failing, so the shape is asserted at run time before anything is
 * dropped.
 *
 * Nothing references game_review.id, so the down() below is a lossless
 * projection back out of reviews.
 *
 * See docs/plans/2026-09-10-relationship-cardinality.md, Unit 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('game_review')
            ->select('review_id')->groupBy('review_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("game_review holds {$duplicates} reviews with more than one game.");
        }

        $orphans = DB::table('reviews')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('game_review')
                ->whereColumn('game_review.review_id', 'reviews.id'))
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException("{$orphans} reviews have no game; the column is NOT NULL.");
        }

        Schema::table('reviews', function (Blueprint $t) {
            $t->integer('game_id')->nullable()->after('id');
        });

        DB::table('reviews')->update([
            'game_id' => DB::raw('(SELECT gr.`game_id` FROM `game_review` gr WHERE gr.`review_id` = `reviews`.`id`)'),
        ]);

        $missing = DB::table('reviews')->whereNull('game_id')->count();

        if ($missing > 0) {
            throw new RuntimeException("{$missing} reviews have no game_id after the backfill; refusing to drop game_review.");
        }

        // Created nullable and tightened afterwards so the column ends up NOT
        // NULL with no default, which is what game_review.game_id is.
        Schema::table('reviews', function (Blueprint $t) {
            $t->integer('game_id')->nullable(false)->change();
            $t->foreign('game_id')->references('id')->on('games')->cascadeOnDelete();
        });

        // Last, and only once the data is provably in its new home: MariaDB
        // will not roll a DDL statement back, so the ordering is the safety.
        Schema::drop('game_review');
    }

    public function down(): void
    {
        Schema::create('game_review', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('review_id');
            $t->integer('game_id');
            $t->foreign('review_id')->references('id')->on('reviews')->cascadeOnDelete();
            $t->foreign('game_id')->references('id')->on('games')->cascadeOnDelete();
        });

        DB::table('game_review')->insertUsing(
            ['review_id', 'game_id'],
            DB::table('reviews')->select('id', 'game_id')
        );

        Schema::table('reviews', function (Blueprint $t) {
            $t->dropForeign(['game_id']);
            $t->dropColumn('game_id');
        });
    }
};
