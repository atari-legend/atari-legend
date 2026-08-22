<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website.website_id to id.
 *
 * website_category_cross.website_id is the pivot's foreign key and stays, as does website_validate.website_id -- that table uses its parent's key as its own, which is a schema change rather than a rename. See "Not renameable by this plan".
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website', function (Blueprint $table) {
            $table->renameColumn('website_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('website', function (Blueprint $table) {
            $table->renameColumn('id', 'website_id');
        });
    }
};
