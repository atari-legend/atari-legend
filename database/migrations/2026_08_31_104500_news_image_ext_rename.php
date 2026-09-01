<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_images.news_image_ext to imgext.
 *
 * The image's file extension, on the schema's majority spelling.
 * news.news_image_id is a foreign key and does not move.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_images', fn (Blueprint $t) => $t->renameColumn('news_image_ext', 'imgext'));
    }

    public function down(): void
    {
        Schema::table('news_images', fn (Blueprint $t) => $t->renameColumn('imgext', 'news_image_ext'));
    }
};
