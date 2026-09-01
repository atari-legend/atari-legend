<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename articles.article_text to text.
 *
 * The article body.
 *
 * Unit 8 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('article_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('text', 'article_text'));
    }
};
