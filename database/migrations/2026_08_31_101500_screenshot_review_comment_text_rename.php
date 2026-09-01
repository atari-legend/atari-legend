<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_review_comments.comment_text to text.
 *
 * A screenshot description, the review half of the same word-census finding.
 * ScreenshotReviewComment declares no $fillable and its writes are direct
 * attribute assignment, so a missed site fails loudly.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_review_comments', fn (Blueprint $t) => $t->renameColumn('comment_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('screenshot_review_comments', fn (Blueprint $t) => $t->renameColumn('text', 'comment_text'));
    }
};
