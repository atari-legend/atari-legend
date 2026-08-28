<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Constrain the ten game-side columns that were foreign keys in every sense
 * except the constraint.
 *
 * In two halves: the orphans go first, because a constraint cannot be added
 * over a row whose parent is missing.
 *
 * The cleanup deletes 27 rows -- 17 screenshot_game, 2 sub_crew, 4
 * game_similar on game_id, 1 game_similar on game_similar_cross, 3 game_vs --
 * plus the 17 screenshots rows behind the orphaned pivot rows, which are
 * referenced by nothing else once those are gone. The filenames are in the
 * commit message so the JPEGs on disk can be swept separately; this migration
 * does not touch the filesystem.
 *
 * Every count is asserted before its delete, so that data drifting since the
 * census was taken aborts the migration rather than quietly deleting a
 * different set of rows. The assertion is one-sided -- more than expected is
 * an error, fewer is not -- because a migrate:fresh database legitimately has
 * none of them, and the same migration has to run there. The counts are read
 * back out of the database rather than trusted from a comment.
 *
 * All nine game-side constraints are ON DELETE CASCADE, which matches both
 * what the delete code already does by hand and what every other existing
 * child of game does. bug_report.bug_report_type_id is SET NULL: the column is
 * nullable and a bug report outlives its category.
 *
 * Two things this deliberately does not do:
 *
 * - menu_disk_contents.game_id stays RESTRICT. It is the one child of game
 *   without an ON DELETE clause, and a row there records what was actually on
 *   a disk -- a historical fact about the disk, not a derived property of the
 *   game -- so CASCADE would silently delete 1,334 of them.
 * - No index is dropped anywhere. Adding a constraint creates no new index
 *   when a usable one already exists; InnoDB binds the foreign key to it, and
 *   a later drop then fails with 1553. screenshot_game.game_id is the case
 *   that matters.
 *
 * These constraints change nothing at runtime today.
 * Game::getIsDeletableAttribute() already refuses to delete a game with any of
 * these children, and GameController::destroy() refuses the request before a
 * DELETE is issued. They take effect only if that guard is removed or
 * bypassed. One coupling to note if it ever is: the screenshot_game.game_id
 * cascade deletes the pivot row and leaves the screenshots row and the JPEG on
 * disk, so relaxing the guard turns this into a file leak.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 3a.
 */
return new class extends Migration
{
    /**
     * child table => [child column, parent table, orphan count on 2026-08-27].
     */
    private const ORPHANS = [
        ['screenshot_game', 'game_id', 'game', 17],
        ['sub_crew', 'crew_id', 'crew', 2],
        ['game_similar', 'game_id', 'game', 4],
        ['game_similar', 'game_similar_cross', 'game', 1],
        ['game_vs', 'atari_id', 'game', 3],
    ];

    /**
     * child table => [child column, parent table, ON DELETE rule].
     */
    private const CONSTRAINTS = [
        ['game_release', 'game_id', 'game', 'cascade'],
        ['game_aka', 'game_id', 'game', 'cascade'],
        ['screenshot_game', 'game_id', 'game', 'cascade'],
        ['screenshot_game', 'screenshot_id', 'screenshots', 'cascade'],
        ['sub_crew', 'crew_id', 'crew', 'cascade'],
        ['sub_crew', 'parent_id', 'crew', 'cascade'],
        ['game_similar', 'game_id', 'game', 'cascade'],
        ['game_similar', 'game_similar_cross', 'game', 'cascade'],
        ['game_vs', 'atari_id', 'game', 'cascade'],
        ['bug_report', 'bug_report_type_id', 'bug_report_type', 'set null'],
    ];

    /**
     * The pivots that can hold a screenshots row alive, screenshot_game aside.
     */
    private const SCREENSHOT_PIVOTS = [
        'spotlight',
        'screenshot_article',
        'screenshot_game_fact',
        'screenshot_game_submitinfo',
        'screenshot_interview',
        'screenshot_review',
    ];

    public function up(): void
    {
        $strandedScreenshots = $this->orphanQuery('screenshot_game', 'game_id', 'game')
            ->pluck('screenshot_id')
            ->filter()
            ->all();

        foreach (self::ORPHANS as [$table, $column, $parent, $expected]) {
            $this->deleteOrphans($table, $column, $parent, $expected);
        }

        $this->deleteStrandedScreenshots($strandedScreenshots);

        foreach (self::CONSTRAINTS as [$table, $column, $parent, $rule]) {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $rule) {
                $blueprint->foreign($column)->references('id')->on($parent)->onDelete($rule);
            });
        }
    }

    /**
     * Drops the ten constraints and stops.
     *
     * This is not a lossless reverse and cannot be: up() deletes rows, and a
     * down() has no way of knowing which rows those were or that they were
     * orphans. The dump taken immediately before the deploy is the recovery
     * path for the data half.
     */
    public function down(): void
    {
        foreach (array_reverse(self::CONSTRAINTS) as [$table, $column, $parent, $rule]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
        }
    }

    /**
     * The rows of $table whose $column names a $parent row that is not there.
     */
    private function orphanQuery(string $table, string $column, string $parent)
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, DB::table($parent)->select('id'))
            ->get();
    }

    private function deleteOrphans(string $table, string $column, string $parent, int $expected): void
    {
        $found = $this->orphanQuery($table, $column, $parent)->count();

        if ($found > $expected) {
            throw new RuntimeException(
                "{$table}.{$column} has {$found} orphaned rows, more than the {$expected} this migration "
                . 'was written against. Re-run the census in the plan and update it before deleting anything.'
            );
        }

        DB::table($table)
            ->whereNotNull($column)
            ->whereNotIn($column, DB::table($parent)->select('id'))
            ->delete();
    }

    /**
     * Delete the screenshots rows that the orphaned screenshot_game rows were
     * the last thing referencing.
     *
     * Re-checked against every other pivot rather than assumed: on 2026-08-27
     * all 17 were referenced once and only from screenshot_game, but a
     * screenshot that has since been reused somewhere else must survive.
     */
    private function deleteStrandedScreenshots(array $ids): void
    {
        foreach ($ids as $id) {
            $stillUsed = DB::table('screenshot_game')->where('screenshot_id', $id)->exists();

            foreach (self::SCREENSHOT_PIVOTS as $pivot) {
                $stillUsed = $stillUsed || DB::table($pivot)->where('screenshot_id', $id)->exists();
            }

            if (! $stillUsed) {
                DB::table('screenshots')->where('id', $id)->delete();
            }
        }
    }
};
