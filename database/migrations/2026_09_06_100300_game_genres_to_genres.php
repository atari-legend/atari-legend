<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * game_genres becomes genres, and its pivot moves to game_genre.
 *
 * Rule 3 in its pure form: game_genre_cross exists only because a pivot
 * called game_genre would read as the singular of its own parent
 * game_genres. Renaming the parent removes the collision, and the pivot
 * then lands on the name Laravel derives, so Game::genres() drops its
 * explicit table argument.
 *
 * game_genres is a pure foreign-key parent -- TableRenamer rewrites nothing
 * for it. The legacy game_cat_id index on the pivot, which already names a
 * column gone for years, is left exactly as it is; this migration renames
 * the column under it and does not touch the index.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 3.
 */
return new class extends Migration
{
    private const TABLES = [
        'game_genres'      => 'genres',
        'game_genre_cross' => 'game_genre',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        Schema::table('game_genre', fn (Blueprint $t) => $t->renameColumn('game_genre_id', 'genre_id'));
    }

    public function down(): void
    {
        Schema::table('game_genre', fn (Blueprint $t) => $t->renameColumn('genre_id', 'game_genre_id'));

        TableRenamer::reverse(self::TABLES);
    }
};
