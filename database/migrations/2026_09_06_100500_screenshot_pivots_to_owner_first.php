<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rule 1 applied to the six pivots that attach a screenshot to something.
 *
 * The seventh -- screenshot_game_submitinfo -- is Unit 4's, deliberately: it
 * would otherwise be renamed twice.
 *
 * Three parent/child pairs are renamed in one migration each: every
 * screenshot_* pivot is the parent of its screenshot_*_comments table.
 * Order inside TABLES does not matter -- InnoDB rewrites each child's
 * referenced name as soon as its parent is renamed, whichever runs first.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 5.
 */
return new class extends Migration
{
    private const TABLES = [
        'screenshot_game'               => 'game_screenshot',
        'screenshot_game_fact'          => 'game_fact_screenshot',
        'screenshot_article'            => 'article_screenshot',
        'screenshot_interview'          => 'interview_screenshot',
        'screenshot_review'             => 'review_screenshot',
        'screenshot_article_comments'   => 'article_screenshot_comments',
        'screenshot_interview_comments' => 'interview_screenshot_comments',
        'screenshot_review_comments'    => 'review_screenshot_comments',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        Schema::table('article_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('screenshot_article_id', 'article_screenshot_id'));
        Schema::table('interview_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('screenshot_interview_id', 'interview_screenshot_id'));
        Schema::table('review_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('screenshot_review_id', 'review_screenshot_id'));
    }

    public function down(): void
    {
        Schema::table('review_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('review_screenshot_id', 'screenshot_review_id'));
        Schema::table('interview_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('interview_screenshot_id', 'screenshot_interview_id'));
        Schema::table('article_screenshot_comments', fn (Blueprint $t) => $t->renameColumn('article_screenshot_id', 'screenshot_article_id'));

        TableRenamer::reverse(self::TABLES);
    }
};
