<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * The four comment pivots and review_game take owner-first.
 *
 * The four *_user_comments tables are the survey's C1: plural where every
 * sibling pivot is singular, and carrying the word `user` while their
 * foreign keys go to articles/games/interviews/reviews and comments, never
 * to users. review_game is the survey's C5: under Rule 1 the more primary
 * entity leads, and games leads reviews, so it becomes game_review.
 *
 * No column moves in this unit -- five tables, ten constraints, nothing
 * left behind.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 6.
 */
return new class extends Migration
{
    private const TABLES = [
        'article_user_comments'   => 'article_comment',
        'game_user_comments'      => 'game_comment',
        'interview_user_comments' => 'interview_comment',
        'review_user_comments'    => 'review_comment',
        'review_game'             => 'game_review',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);
    }

    public function down(): void
    {
        TableRenamer::reverse(self::TABLES);
    }
};
