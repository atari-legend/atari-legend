<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_validates.website_category to website_category_id.
 *
 * An int(11) naming a website_categories row without the _id suffix. It carries
 * no constraint, so it never appeared in the foreign-key census and is here on
 * its own evidence; the table holds 0 rows and nothing in app, resources,
 * tests, database/factories or database/seeders names the column.
 *
 * Unit 2 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_category', 'website_category_id'));
    }

    public function down(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_category_id', 'website_category'));
    }
};
