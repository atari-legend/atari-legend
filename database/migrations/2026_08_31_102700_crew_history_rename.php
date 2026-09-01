<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename crews.crew_history to history.
 *
 * The crew's history text.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('crew_history', 'history'));
    }

    public function down(): void
    {
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('history', 'crew_history'));
    }
};
