<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_validates.website_description to description.
 *
 * The submission's description. websites already holds an unprefixed
 * description, but that is a different table, so this is not a collision --
 * one of the two candidates a reader will look for.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_description', 'description'));
    }

    public function down(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('description', 'website_description'));
    }
};
