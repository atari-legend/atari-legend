<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_validates.website_date to date.
 *
 * The date the submission was made, still an int(11) unix timestamp.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('date', 'website_date'));
    }
};
