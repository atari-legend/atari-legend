<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_user_comments.game_user_comments_id to id.
 *
 * No model, relationship, Blade template, factory or seeder reads this column:
 * the table is model-less or reached only as a pivot, and a belongsToMany
 * derives its keys from the model names. The only files naming it are the
 * historical create_* migration and, where one exists, an older data
 * migration -- both of which run before this one in date order and are left
 * alone.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_user_comments', fn (Blueprint $t) => $t->renameColumn('game_user_comments_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('game_user_comments', fn (Blueprint $t) => $t->renameColumn('id', 'game_user_comments_id'));
    }
};
