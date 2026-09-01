<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_facts.game_fact to fact.
 *
 * The row content, named after its own table. `fact` is the bare content word;
 * the table stays named for what it holds.
 *
 * The game_fact token survives as a Screenshot storage-folder key
 * (getUrl/getPath/getFolder("game_fact") -> game_fact_screenshots), which is
 * not the column and does not move.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_facts', fn (Blueprint $t) => $t->renameColumn('game_fact', 'fact'));
    }

    public function down(): void
    {
        Schema::table('game_facts', fn (Blueprint $t) => $t->renameColumn('fact', 'game_fact'));
    }
};
