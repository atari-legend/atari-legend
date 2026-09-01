<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_text to text.
 *
 * The review body.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('text', 'review_text'));
    }
};
