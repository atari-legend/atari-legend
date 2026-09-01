<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename crews.crew_name to name.
 *
 * The crew's display name, the third of the three `name` targets in this
 * unit.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('crew_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('name', 'crew_name'));
    }
};
