<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the pre-Laravel inverted index over news headlines and bodies.
 *
 * news_search_wordlist (6,584 rows) and news_search_wordmatch (32,314 rows).
 * Neither has a model, a relation, or a reference outside the historical
 * create_* migrations, and nothing writes them -- the newest row cannot even be
 * dated, since neither table has a timestamp column.
 *
 * These 38,898 rows are derived data: an index over news is rebuildable from
 * news, which this migration does not touch.
 *
 * Neither carries a foreign key. news_search_wordmatch.news_id is a
 * mediumint(8) unsigned that does not match news.id's int(11), so no
 * constraint could be added without changing its type.
 *
 * down() restores structure, never rows, including news_search_wordlist's
 * primary key on news_word_text, its auto-incrementing news_word_id -- which
 * SQLite cannot express as a non-primary-key column, exactly as the original
 * create migration found -- and the two secondary indexes on
 * news_search_wordmatch.
 *
 * See docs/plans/2026-08-28-dead-tables-and-columns.md, unit 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('news_search_wordmatch');
        Schema::dropIfExists('news_search_wordlist');
    }

    public function down(): void
    {
        Schema::create('news_search_wordlist', function (Blueprint $table) {
            $table->string('news_word_text', 50)->default('')->primary();
            $table->unsignedMediumInteger('news_word_id')->index('news_word_id');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `news_search_wordlist` MODIFY `news_word_id` mediumint UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        Schema::create('news_search_wordmatch', function (Blueprint $table) {
            $table->unsignedMediumInteger('news_id')->default(0)->index('news_id');
            $table->unsignedMediumInteger('news_word_id')->default(0)->index('news_word_id');
            $table->boolean('news_title_match')->default(0);
        });
    }
};
