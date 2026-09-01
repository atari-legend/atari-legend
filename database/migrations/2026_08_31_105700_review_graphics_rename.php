<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_graphics to graphics.
 *
 * The graphics score. The four score columns keep their words and lose the
 * stem; they stay nullable int(11), the state the main-text merge plan's
 * decision 2 preserved.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_graphics', 'graphics'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('graphics', 'review_graphics'));
    }
};
