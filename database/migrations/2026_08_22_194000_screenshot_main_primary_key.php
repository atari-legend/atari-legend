<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_main.screenshot_id to id.
 *
 * Six pivots carry a screenshot_id foreign key -- screenshot_game,
 * screenshot_article, screenshot_interview, screenshot_review,
 * screenshot_game_fact and screenshot_game_submitinfo -- as does
 * spotlight.screenshot_id. All keep their names; only the parent's own key
 * moves.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_main', function (Blueprint $table) {
            $table->renameColumn('screenshot_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('screenshot_main', function (Blueprint $table) {
            $table->renameColumn('id', 'screenshot_id');
        });
    }
};
