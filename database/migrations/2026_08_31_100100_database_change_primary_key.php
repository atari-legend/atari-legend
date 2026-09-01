<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename database_change.database_change_id to id.
 *
 * The pre-Laravel schema ledger, 267 rows, kept by the dead-tables review for
 * its data. Like game_gallery it has no model and no reader outside
 * database/migrations, and its primary key is its only index.
 *
 * The table keeps its singular name: it is a kept record table, not a
 * model-backed entity, so the plural rule does not reach it.
 *
 * Unit 1 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('database_change', fn (Blueprint $t) => $t->renameColumn('database_change_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('database_change', fn (Blueprint $t) => $t->renameColumn('id', 'database_change_id'));
    }
};
