<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_edit to edit.
 *
 * The published/unpublished flag, read through Review::REVIEW_PUBLISHED and
 * REVIEW_UNPUBLISHED. ReviewsTable joins games, so the builder qualifies it
 * while it moves.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_edit', 'edit'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('edit', 'review_edit'));
    }
};
