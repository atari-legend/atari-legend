<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_submissions.news_text to text.
 *
 * The submission's body, mirroring news.text.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('news_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('text', 'news_text'));
    }
};
