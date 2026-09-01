<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news.news_date to date.
 *
 * The publication date. It stays an int(11) unix timestamp; the News model's
 * $casts entry moves with the column and keeps its datetime:timestamp cast.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', fn (Blueprint $t) => $t->renameColumn('news_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('news', fn (Blueprint $t) => $t->renameColumn('date', 'news_date'));
    }
};
