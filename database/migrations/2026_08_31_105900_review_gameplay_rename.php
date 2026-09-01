<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_gameplay to gameplay.
 *
 * The gameplay score.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_gameplay', 'gameplay'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('gameplay', 'review_gameplay'));
    }
};
