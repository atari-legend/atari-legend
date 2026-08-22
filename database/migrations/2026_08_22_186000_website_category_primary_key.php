<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_category.website_category_id to id.
 *
 * website_category_cross.website_category_id is the pivot's foreign key and
 * keeps its name, as do both belongsToMany arguments that point at it.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B step 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_category', function (Blueprint $table) {
            $table->renameColumn('website_category_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('website_category', function (Blueprint $table) {
            $table->renameColumn('id', 'website_category_id');
        });
    }
};
