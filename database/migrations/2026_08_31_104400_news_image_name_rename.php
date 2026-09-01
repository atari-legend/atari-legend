<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_images.news_image_name to name.
 *
 * A legacy filename nothing reads; the dead-tables campaign kept it for the
 * data. Zero code references, so this half of the table is a migration only.
 *
 * Unit 7 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_images', fn (Blueprint $t) => $t->renameColumn('news_image_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('news_images', fn (Blueprint $t) => $t->renameColumn('name', 'news_image_name'));
    }
};
