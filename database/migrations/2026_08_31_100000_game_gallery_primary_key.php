<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_gallery.game_gallery_id to id.
 *
 * The primary-key campaign left the 26 model-less tables alone; the dead-tables
 * review then kept this one (118 rows of magazine adverts), so it still carries
 * a prefixed key in an otherwise all-`id` schema. Nothing outside
 * database/migrations names the column: no model, no relation, no reader. The
 * key is the table's only index, so the rename leaves nothing stale.
 *
 * Unit 1 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_gallery', fn (Blueprint $t) => $t->renameColumn('game_gallery_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('game_gallery', fn (Blueprint $t) => $t->renameColumn('id', 'game_gallery_id'));
    }
};
