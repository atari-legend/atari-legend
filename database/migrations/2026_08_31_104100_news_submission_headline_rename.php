<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_submissions.news_headline to headline.
 *
 * news_submissions mirrors news and moves with it, so a submission keeps the
 * same column names as the news row it becomes.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('news_headline', 'headline'));
    }

    public function down(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('headline', 'news_headline'));
    }
};
