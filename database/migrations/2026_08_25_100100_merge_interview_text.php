<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold interview_text into interview_main and drop it.
 *
 * The same shape as the article merge one file earlier, with one difference
 * that is easy to miss by copying it: interview_intro and interview_chapters
 * are nullable in production and stay nullable here, on the way in and on the
 * way back. One interview has a NULL interview_chapters, so a down() that
 * declared all four columns NOT NULL -- Laravel's default -- would abort its
 * insertUsing under strict mode on that single row.
 *
 * interview_text stays TEXT rather than being widened to MEDIUMTEXT while it
 * is being recreated. The longest one is at 72% of the 65,535-byte ceiling,
 * and strict mode makes an over-long save a loud SQLSTATE 22001 in front of
 * the contributor writing it, never a silent truncation.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('interview_text')
            ->select('interview_id')->groupBy('interview_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("interview_text holds {$duplicates} interviews with more than one row.");
        }

        $expected = DB::table('interview_text')->count();

        Schema::table('interview_main', function (Blueprint $t) {
            $t->text('interview_text')->nullable()->after('individual_id');
            $t->integer('interview_date')->nullable()->after('interview_text');
            $t->mediumText('interview_intro')->nullable()->after('interview_date');
            $t->mediumText('interview_chapters')->nullable()->after('interview_intro');
        });

        foreach (['interview_text', 'interview_date', 'interview_intro', 'interview_chapters'] as $column) {
            DB::table('interview_main')->update([
                $column => DB::raw("(SELECT t.`{$column}` FROM `interview_text` t WHERE t.`interview_id` = `interview_main`.`id`)"),
            ]);
        }

        $moved = DB::table('interview_main')->whereNotNull('interview_text')->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} interview_text rows; refusing to drop the table.");
        }

        // Only the two columns that were NOT NULL on the child are tightened;
        // the intro and the chapters keep the nullability they have today.
        Schema::table('interview_main', function (Blueprint $t) {
            $t->text('interview_text')->nullable(false)->change();
            $t->integer('interview_date')->nullable(false)->change();
        });

        Schema::drop('interview_text');
    }

    public function down(): void
    {
        Schema::create('interview_text', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('interview_id');
            $t->text('interview_text');
            $t->integer('interview_date');
            $t->mediumText('interview_intro')->nullable();
            $t->mediumText('interview_chapters')->nullable();
            $t->foreign('interview_id')->references('id')->on('interview_main')->cascadeOnDelete();
        });

        DB::table('interview_text')->insertUsing(
            ['interview_id', 'interview_text', 'interview_date', 'interview_intro', 'interview_chapters'],
            DB::table('interview_main')
                ->select('id', 'interview_text', 'interview_date', 'interview_intro', 'interview_chapters')
        );

        Schema::table('interview_main', function (Blueprint $t) {
            $t->dropColumn(['interview_text', 'interview_date', 'interview_intro', 'interview_chapters']);
        });
    }
};
