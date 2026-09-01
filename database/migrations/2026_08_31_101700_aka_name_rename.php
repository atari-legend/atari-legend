<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_akas.aka_name to name.
 *
 * The same display name on the alternate-title table, and a word-census
 * finding: `aka` is the table's second word, so the stem census never saw it.
 * It moves with games.game_name rather than in a unit of its own because the
 * two columns are read together everywhere they are read at all -- every game
 * autocomplete unions the two tables and every title search checks both. The
 * precedent is already in Ajax/GameAndSoftwareController, which selected
 * 'game_name as name' and 'aka_name as name' side by side; both aliases are now
 * identities and go.
 *
 * The two whereHas('akas') closures stay unqualified: the correlated subquery's
 * innermost scope is game_akas alone, so a bare `name` there resolves to this
 * column and not to games.name.
 *
 * Unit 4 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_akas', fn (Blueprint $t) => $t->renameColumn('aka_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('game_akas', fn (Blueprint $t) => $t->renameColumn('name', 'aka_name'));
    }
};
