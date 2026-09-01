<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_validates.website_url to url.
 *
 * The submission's address.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_url', 'url'));
    }

    public function down(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('url', 'website_url'));
    }
};
