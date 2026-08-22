<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename article_text.article_text_id to id.
 *
 * Ships with the article_main rename, because the two are joined to each other
 * in four places and this pair is what makes both sides of that join expose a
 * column called `id`. That collision is the whole reason Phase A2 exists: those
 * selects were qualified in 40b92c5, so the join is safe -- but this is the
 * first rename that actually depends on it.
 *
 * article_text.article_id stays: it is the foreign key to article_main.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase A2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_text', function (Blueprint $table) {
            $table->renameColumn('article_text_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('article_text', function (Blueprint $table) {
            $table->renameColumn('id', 'article_text_id');
        });
    }
};
