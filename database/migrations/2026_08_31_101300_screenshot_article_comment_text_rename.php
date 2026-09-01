<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_article_comments.comment_text to text.
 *
 * A screenshot description. A word-census finding: `comment` is the table's
 * third word.
 *
 * ScreenshotArticleComment declares $fillable = ["comment_text"] and
 * Admin/Articles/ArticleController writes through it, so the model change ships
 * in this same commit: with preventSilentlyDiscardingAttributes off in
 * production, a stale $fillable entry drops the description silently.
 *
 * The ScreenshotArticle::comment() relation name is not the column;
 * $screenshot->pivot->comment stays and only the ->comment_text on the end of
 * it moves.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_article_comments', fn (Blueprint $t) => $t->renameColumn('comment_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('screenshot_article_comments', fn (Blueprint $t) => $t->renameColumn('text', 'comment_text'));
    }
};
