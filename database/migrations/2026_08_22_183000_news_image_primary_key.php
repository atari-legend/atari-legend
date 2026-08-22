<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_image.news_image_id to id.
 *
 * news.news_image_id points here and keeps its name: it is the foreign key,
 * and `news.news_image_id` referencing `news_image.id` is already the Laravel
 * convention. Only the parent's own key moves.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B step 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_image', function (Blueprint $table) {
            $table->renameColumn('news_image_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('news_image', function (Blueprint $table) {
            $table->renameColumn('id', 'news_image_id');
        });
    }
};
