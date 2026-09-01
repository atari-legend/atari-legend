<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename database_change.database_change_script to script.
 *
 * The ledger's SQL text, carrying the table's name as a prefix. No code
 * references, so this is a migration with no code side. The table's
 * database_update_id column is not a foreign key and is not renamed.
 *
 * Unit 1 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('database_change', fn (Blueprint $t) => $t->renameColumn('database_change_script', 'script'));
    }

    public function down(): void
    {
        Schema::table('database_change', fn (Blueprint $t) => $t->renameColumn('script', 'database_change_script'));
    }
};
