<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individuals.ind_name to name.
 *
 * The person's display name. `ind` is an abbreviation, so neither the stem
 * census nor the word census could see this column or its three siblings: the
 * four individuals.ind_* renames are in the campaign on the hand application
 * alone, which is the standing limit of the method.
 *
 * The third table to take `name` in two units, after games and game_akas, and
 * the first of three here. All three land on different tables, so a join
 * qualifies rather than collides.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('ind_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('name', 'ind_name'));
    }
};
