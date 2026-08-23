<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename review_comments.review_comments_id to id.
 *
 * Holds the caption for a review screenshot; screenshot_review_id on it is the foreign key and stays.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_comments', function (Blueprint $table) {
            $table->renameColumn('review_comments_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('review_comments', function (Blueprint $table) {
            $table->renameColumn('id', 'review_comments_id');
        });
    }
};
