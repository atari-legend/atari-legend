<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_fact.game_fact_id to id.
 *
 * screenshot_game_fact.game_fact_id is the pivot's foreign key and stays.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_fact', function (Blueprint $table) {
            $table->renameColumn('game_fact_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('game_fact', function (Blueprint $table) {
            $table->renameColumn('id', 'game_fact_id');
        });
    }
};
