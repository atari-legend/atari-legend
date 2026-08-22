<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename article_comments.article_comment_id to id.
 *
 * This table holds the caption for an article screenshot, reached through
 * ScreenshotArticle; screenshot_article_id on it is the foreign key and stays.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->renameColumn('article_comment_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('article_comments', function (Blueprint $table) {
            $table->renameColumn('id', 'article_comment_id');
        });
    }
};
