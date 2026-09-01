<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_overall to overall.
 *
 * The overall score. reviews.user_id is a foreign key and does not move.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_overall', 'overall'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('overall', 'review_overall'));
    }
};
