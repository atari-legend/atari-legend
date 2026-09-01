<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename articles.article_date to date.
 *
 * The publication date. It stays an int(11) unix timestamp and Article's
 * $casts entry moves with it, keeping the datetime:timestamp cast.
 * articles.article_type_id is a foreign key and does not move.
 *
 * Unit 8 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('article_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $t) => $t->renameColumn('date', 'article_date'));
    }
};
