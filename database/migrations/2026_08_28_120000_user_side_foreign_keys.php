<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settle what happens to a person's contributions when their account goes.
 *
 * The policy: a user can be deleted; what they wrote stays. No constraint
 * referencing users may CASCADE. Nullable columns get SET NULL. NOT NULL
 * columns get no constraint at all rather than a column change, because the
 * frontend already renders a dangling user_id as a missing author --
 * Comment::user() is a belongsTo and returns null for a dangling id exactly as
 * for a null one, and Helper::user() is typed ?User and answers 'Former user'.
 * A RESTRICT is acceptable only where a guard refuses the delete first, which
 * is what User::getIsDeletableAttribute() is for.
 *
 * Four changes.
 *
 * game_votes.user_id becomes nullable and its constraint is re-ruled from
 * CASCADE to SET NULL, so the vote survives as an anonymous one. It is the
 * only CASCADE pointing at users and the only destructive rule in the set.
 * Voting is sparse -- 1,470 votes over 885 games, 552 of which have exactly
 * one voter -- so deleting one active account could blank the score on
 * hundreds of game pages. Nothing displays a vote as a particular user's:
 * Game::getScoreAttribute() averages live, and TopGames,
 * GameController::show() and AdminStatisticsHelper::voteDistribution() all
 * aggregate without touching user_id.
 *
 * Two properties make it safe. The UNIQUE (game_id, user_id) index is kept,
 * and MySQL does not collide NULLs in a unique index, so anonymised votes
 * coexist on the same game. And GameVoteController::findVote() matches on
 * $user->getKey(), which is never null, so an anonymised row can never be
 * returned as somebody's own vote.
 *
 * menu_disk_dumps.user_id and bug_report.user_id are nullable, measure zero
 * orphans, and take SET NULL.
 *
 * menu_disk_contents.menu_software_id takes CASCADE. It matches
 * magazine_indices.menu_software_id, the only other constraint pointing at
 * menu_software, which already cascades, and MenuSoftwareController::destroy()
 * is already a bare delete with no guard, so deleting a software entry already
 * drops its magazine index rows silently. A software entry and the content
 * rows naming it are one record of one thing -- unlike a game, which exists
 * independently of the disks it appeared on.
 *
 * Deliberately left unconstrained: comments.user_id and
 * news_submission.user_id are NOT NULL, and making a column nullable to gain
 * SET NULL is a data change rather than a constraint change.
 * change_log.user_id has 1,048 rows whose user is gone and is an audit trail;
 * users_login_attempts.user_id has 118, on a rate-limiting scratchpad;
 * users_reset.user_id has 11, on a dead table.
 *
 * The three RESTRICTs -- game_submitinfo, dump, dump_user_info -- are not
 * touched. What changes is that nothing reaches them any more; see
 * User::getIsDeletableAttribute(), which ships in the same commit.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 3b.
 */
return new class extends Migration
{
    /**
     * child table => [child column, parent table, ON DELETE rule].
     */
    private const CONSTRAINTS = [
        ['menu_disk_dumps', 'user_id', 'users', 'set null'],
        ['bug_report', 'user_id', 'users', 'set null'],
        ['menu_disk_contents', 'menu_software_id', 'menu_software', 'cascade'],
    ];

    public function up(): void
    {
        Schema::table('game_votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('game_votes', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->change();
        });

        Schema::table('game_votes', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        foreach (self::CONSTRAINTS as [$table, $column, $parent, $rule]) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $rule) {
                $blueprint->foreign($column)->references('id')->on($parent)->onDelete($rule);
            });
        }
    }

    /**
     * Restoring the CASCADE also has to put the column back to NOT NULL, which
     * only works while no vote has been anonymised. That is the honest reverse:
     * a rollback after a user has been deleted is a rollback over data the old
     * schema cannot hold, and the pre-deploy dump is the recovery path.
     */
    public function down(): void
    {
        foreach (array_reverse(self::CONSTRAINTS) as [$table, $column, $parent, $rule]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
        }

        Schema::table('game_votes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::table('game_votes')->whereNull('user_id')->delete();

        Schema::table('game_votes', function (Blueprint $table) {
            $table->integer('user_id')->nullable(false)->change();
        });

        Schema::table('game_votes', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
