<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_aka.game_aka_id to id.
 *
 * game_aka.game_id is the foreign key to game and keeps its name.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_aka', function (Blueprint $table) {
            $table->renameColumn('game_aka_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('game_aka', function (Blueprint $table) {
            $table->renameColumn('id', 'game_aka_id');
        });
    }
};
