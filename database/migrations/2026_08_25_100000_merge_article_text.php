<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold article_text into article_main and drop it.
 *
 * article_main carries no content at all -- user_id, article_type_id, draft --
 * so every reader of an article reaches through ->texts->first(). The pair is
 * strictly 1:1 with no orphans, but nothing in the schema says so: the child
 * has a surrogate id and a plain KEY, not a unique one. So the shape is
 * asserted at run time rather than assumed, twice, before anything is dropped.
 *
 * Nothing references article_text.id, so the down() below is a lossless
 * projection back out of the parent.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('article_text')
            ->select('article_id')->groupBy('article_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("article_text holds {$duplicates} articles with more than one row.");
        }

        $expected = DB::table('article_text')->count();

        Schema::table('article_main', function (Blueprint $t) {
            $t->mediumText('article_title')->nullable()->after('article_type_id');
            $t->mediumText('article_text')->nullable()->after('article_title');
            $t->integer('article_date')->nullable()->after('article_text');
            $t->mediumText('article_intro')->nullable()->after('article_date');
        });

        foreach (['article_title', 'article_text', 'article_date', 'article_intro'] as $column) {
            DB::table('article_main')->update([
                $column => DB::raw("(SELECT t.`{$column}` FROM `article_text` t WHERE t.`article_id` = `article_main`.`id`)"),
            ]);
        }

        $moved = DB::table('article_main')->whereNotNull('article_title')->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} article_text rows; refusing to drop the table.");
        }

        // The columns are created nullable and tightened afterwards, rather
        // than created NOT NULL DEFAULT '', so that the merged table
        // reproduces article_text's nullability exactly: NOT NULL, no default.
        Schema::table('article_main', function (Blueprint $t) {
            $t->mediumText('article_title')->nullable(false)->change();
            $t->mediumText('article_text')->nullable(false)->change();
            $t->integer('article_date')->nullable(false)->change();
            $t->mediumText('article_intro')->nullable(false)->change();
        });

        // Last, and only once the data is provably in its new home: MariaDB
        // will not roll a DDL statement back, so the ordering is the safety.
        Schema::drop('article_text');
    }

    public function down(): void
    {
        Schema::create('article_text', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('article_id');
            $t->mediumText('article_title');
            $t->mediumText('article_text');
            $t->integer('article_date');
            $t->mediumText('article_intro');
            $t->foreign('article_id')->references('id')->on('article_main')->cascadeOnDelete();
        });

        DB::table('article_text')->insertUsing(
            ['article_id', 'article_title', 'article_text', 'article_date', 'article_intro'],
            DB::table('article_main')->select('id', 'article_title', 'article_text', 'article_date', 'article_intro')
        );

        Schema::table('article_main', function (Blueprint $t) {
            $t->dropColumn(['article_title', 'article_text', 'article_date', 'article_intro']);
        });
    }
};
