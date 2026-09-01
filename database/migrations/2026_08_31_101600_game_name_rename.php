<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename games.game_name to name.
 *
 * The game's display name, and the campaign's largest token: 398 lines in 134
 * files name it. `name` is the column every lookup table uses for its label and
 * the one magazines, menu_sets and crews converge on; games holds no name
 * column today, so the target is free.
 *
 * The token survives the rename in three shapes that are not the column, and
 * all three stay: the autocomplete form fields (name="game_name",
 * id="game_name", old('game_name')), which nothing reads -- no controller
 * touches $request->name, the companion hidden field carries the value;
 * the MenuImport wizard's own state key, which sits beside `name` in the same
 * array and so cannot take it; and the e2e helpers that address those fields by
 * id. The data-autocomplete-key attributes do move: they name a property of the
 * endpoint's JSON payload, not the form field.
 *
 * Unit 4 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', fn (Blueprint $t) => $t->renameColumn('game_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('games', fn (Blueprint $t) => $t->renameColumn('name', 'game_name'));
    }
};
