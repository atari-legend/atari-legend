<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename articles.article_title to title.
 *
 * The article's title. One of the four article_* prefixes the main-text merge
 * plan left behind: the merge landed them on articles as article_*, and this
 * unit strips the stem.
 *
 * Unit 8 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('article_title', 'title'));
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('title', 'article_title'));
    }
};
