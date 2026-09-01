<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename websites.website_date to date.
 *
 * The date the link was added. It stays an int(11) unix timestamp; only the
 * name moves.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', fn (Blueprint $t) => $t->renameColumn('website_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('websites', fn (Blueprint $t) => $t->renameColumn('date', 'website_date'));
    }
};
