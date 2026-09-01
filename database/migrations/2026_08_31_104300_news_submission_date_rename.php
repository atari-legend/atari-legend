<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_submissions.news_date to date.
 *
 * The submission date, still an int(11) unix timestamp with a
 * datetime:timestamp cast on NewsSubmission.
 *
 * news_submissions.news_image_id is not touched: it carries no constraint --
 * the table has none at all -- and is an int(11) NOT NULL DEFAULT 0 sentinel
 * rather than a foreign key, so it keeps its _id name.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('news_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('news_submissions', fn (Blueprint $t) => $t->renameColumn('date', 'news_date'));
    }
};
