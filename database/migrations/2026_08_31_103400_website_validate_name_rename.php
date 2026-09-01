<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename website_validates.website_name to name.
 *
 * The submission's display name. website_validates mirrors websites and moves
 * with it, so a submission keeps the same column names as the link it becomes.
 * The table holds 0 rows and is the target of no foreign key; it moves for the
 * name alone.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('website_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('website_validates', fn (Blueprint $t) => $t->renameColumn('name', 'website_name'));
    }
};
