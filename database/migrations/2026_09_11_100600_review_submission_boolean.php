<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * reviews.submission becomes tinyint(1), the type every other true/false column
 * in the schema carries - including reviews.draft, right beside it.
 *
 * It holds 0 and 1 across 127 rows. Review::REVIEW_PUBLISHED is 0 and
 * REVIEW_UNPUBLISHED is 1, and both keep their values: the constants still name
 * what is stored, and the seven query sites that compare them in SQL are
 * unaffected.
 *
 * Skipped on SQLite, which has no distinct integer widths.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->retype(fn (Blueprint $t) => $t->boolean('submission')->default(0));
    }

    public function down(): void
    {
        $this->retype(fn (Blueprint $t) => $t->integer('submission')->default(0));
    }

    private function retype(callable $column): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('reviews', function (Blueprint $t) use ($column) {
            $column($t)->change();
        });
    }
};
