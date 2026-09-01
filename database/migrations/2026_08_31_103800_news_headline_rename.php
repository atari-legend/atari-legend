<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news.news_headline to headline.
 *
 * The news item's headline. The form field is already bare (name="headline").
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', fn (Blueprint $t) => $t->renameColumn('news_headline', 'headline'));
    }

    public function down(): void
    {
        Schema::table('news', fn (Blueprint $t) => $t->renameColumn('headline', 'news_headline'));
    }
};
