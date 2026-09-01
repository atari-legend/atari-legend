<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_game_submitinfo.game_submitinfo_id to game_submit_info_id.
 *
 * The column references game_submit_infos, which the plural campaign renamed
 * from game_submitinfo; the singularised rule now gives game_submit_info_id.
 * This is the one foreign-key break the plural campaign recorded in its Out of
 * scope and priced as a follow-up.
 *
 * The rename converges GameSubmitInfo::screenshots(): Eloquent derives
 * game_submit_info_id from the class name, so both key arguments go, and the
 * relation drops out of RelationshipKeyConventionsTest::DECLINED. The pivot
 * table argument stays -- the derived name would be game_submit_info_screenshot,
 * which is not the table.
 *
 * The constraint and its index keep their old names, by the standing decision.
 *
 * Unit 2 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'screenshot_game_submitinfo',
            fn (Blueprint $t) => $t->renameColumn('game_submitinfo_id', 'game_submit_info_id')
        );
    }

    public function down(): void
    {
        Schema::table(
            'screenshot_game_submitinfo',
            fn (Blueprint $t) => $t->renameColumn('game_submit_info_id', 'game_submitinfo_id')
        );
    }
};
