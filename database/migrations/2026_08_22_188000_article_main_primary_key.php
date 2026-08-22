<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename article_main.article_id to id.
 *
 * article_text.article_id, screenshot_article.article_id and
 * article_user_comments.article_id are foreign keys and keep their names.
 *
 * One renameColumn per migration, so this and the article_text rename beside
 * it are separate files even though they ship together: nothing here runs in a
 * transaction, and a half-applied pair has nothing to unwind it.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_main', function (Blueprint $table) {
            $table->renameColumn('article_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('article_main', function (Blueprint $table) {
            $table->renameColumn('id', 'article_id');
        });
    }
};
