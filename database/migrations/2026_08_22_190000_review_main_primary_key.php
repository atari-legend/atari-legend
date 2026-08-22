<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename review_main.review_id to id.
 *
 * review_game, review_user_comments, screenshot_review and review_score all carry a review_id foreign key and keep it.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_main', function (Blueprint $table) {
            $table->renameColumn('review_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('review_main', function (Blueprint $table) {
            $table->renameColumn('id', 'review_id');
        });
    }
};
