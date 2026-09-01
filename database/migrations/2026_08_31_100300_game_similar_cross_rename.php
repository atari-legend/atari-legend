<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_similar.game_similar_cross to similar_game_id.
 *
 * game_similar is a self-referential pivot: both game_id and this column
 * reference games.id, one row per direction. The old name was the table's own
 * name plus `cross`, not the referenced table's; a table cannot hold two
 * game_id columns, so the second takes a role name and the foreign-key rule
 * (singularised referenced table + _id) is met as far as it can be.
 *
 * The constraint and its index keep their old names: ALTER TABLE ... CHANGE
 * rewrites the constraint's column reference and leaves the name, which is the
 * schema consistency sweep's standing decision.
 *
 * Unit 2 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_similar', fn (Blueprint $t) => $t->renameColumn('game_similar_cross', 'similar_game_id'));
    }

    public function down(): void
    {
        Schema::table('game_similar', fn (Blueprint $t) => $t->renameColumn('similar_game_id', 'game_similar_cross'));
    }
};
