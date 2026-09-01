<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_categories.website_category_name to name.
 *
 * The category's label, on the column every other lookup table uses.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_categories', fn (Blueprint $t) => $t->renameColumn('website_category_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('website_categories', fn (Blueprint $t) => $t->renameColumn('name', 'website_category_name'));
    }
};
