<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * game_submit_infos becomes game_submissions, and its screenshot pivot moves
 * with it.
 *
 * The pivot is the survey's C3: the column-name campaign renamed its column
 * to game_submit_info_id and left the table itself stale, still saying
 * submitinfo while game_submit_infos already said submit_infos. Renaming the
 * parent and the pivot in the same migration closes both spellings at once.
 *
 * This is the one ordering constraint in the plan: Unit 5 renames the other
 * five screenshot pivots and deliberately excludes this one, so the table is
 * renamed once rather than twice.
 *
 * Order inside TABLES does not matter -- the pivot is a child of
 * game_submit_infos, and InnoDB rewrites the child's referenced name as soon
 * as the parent is renamed, whichever runs first.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 4.
 */
return new class extends Migration
{
    private const TABLES = [
        'game_submit_infos'          => 'game_submissions',
        'screenshot_game_submitinfo' => 'game_submission_screenshot',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        Schema::table('game_submission_screenshot', fn (Blueprint $t) => $t->renameColumn('game_submit_info_id', 'game_submission_id'));
    }

    public function down(): void
    {
        Schema::table('game_submission_screenshot', fn (Blueprint $t) => $t->renameColumn('game_submission_id', 'game_submit_info_id'));

        TableRenamer::reverse(self::TABLES);
    }
};
