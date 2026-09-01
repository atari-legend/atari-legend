<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename crews.crew_logo to logo.
 *
 * The crew logo's filename. Unlike the two imgext columns this holds a whole
 * filename rather than an extension, so it keeps its own word.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('crew_logo', 'logo'));
    }

    public function down(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('logo', 'crew_logo'));
    }
};
