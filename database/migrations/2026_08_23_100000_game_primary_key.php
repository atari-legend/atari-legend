<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game.game_id to id.
 *
 * The widest fanout in the campaign and deliberately last: sixteen tables carry a game_id foreign key, and every one of them keeps it. Phase A2 is what makes this safe to do alone -- game joins nearly everything, so the unqualified selects had to go first. Ajax/GameController unions game with game_aka, whose game_id is a genuine foreign key, so game's key is aliased back to game_id there to keep one payload internally consistent.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game', function (Blueprint $table) {
            $table->renameColumn('game_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('game', function (Blueprint $table) {
            $table->renameColumn('id', 'game_id');
        });
    }
};
