<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold interview_screenshot_comments into interview_screenshot as a `description` column and drop it.
 *
 * The caption table is (id, interview_screenshot_id, text) and is strictly 1:1 with the
 * pivot row it points at, but nothing in the schema says so: the child has a
 * surrogate id and a plain KEY, not a unique one. Every reader already treats
 * the caption as a field of the pivot, and every writer creates the two rows
 * together, so the shape is asserted at run time before anything is dropped.
 *
 * Nothing references interview_screenshot_comments.id, so the down() below is a lossless
 * projection back out of the pivot.
 *
 * See docs/plans/2026-09-10-relationship-cardinality.md, Unit 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('interview_screenshot_comments')
            ->select('interview_screenshot_id')->groupBy('interview_screenshot_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($duplicates > 0) {
            throw new RuntimeException("interview_screenshot_comments holds {$duplicates} pivot rows with more than one caption.");
        }

        $expected = DB::table('interview_screenshot_comments')->count();

        Schema::table('interview_screenshot', function (Blueprint $t) {
            $t->mediumText('description')->nullable()->after('screenshot_id');
        });

        DB::table('interview_screenshot')->update([
            'description' => DB::raw('(SELECT c.`text` FROM `interview_screenshot_comments` c WHERE c.`interview_screenshot_id` = `interview_screenshot`.`id`)'),
        ]);

        $moved = DB::table('interview_screenshot')->whereNotNull('description')->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} interview_screenshot_comments rows; refusing to drop the table.");
        }

        // Last, and only once the data is provably in its new home: MariaDB
        // will not roll a DDL statement back, so the ordering is the safety.
        Schema::drop('interview_screenshot_comments');
    }

    public function down(): void
    {
        Schema::create('interview_screenshot_comments', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('interview_screenshot_id');
            $t->mediumText('text');
            $t->foreign('interview_screenshot_id')->references('id')->on('interview_screenshot')->cascadeOnDelete();
        });

        // A pivot row written after the migration with no caption comes back as
        // no caption row, which is the shape this is restoring to.
        DB::table('interview_screenshot_comments')->insertUsing(
            ['interview_screenshot_id', 'text'],
            DB::table('interview_screenshot')->whereNotNull('description')->select('id', 'description')
        );

        Schema::table('interview_screenshot', function (Blueprint $t) {
            $t->dropColumn('description');
        });
    }
};
