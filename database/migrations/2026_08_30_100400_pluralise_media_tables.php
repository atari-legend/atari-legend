<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise the five media tables and rename the three comment tables.
 *
 * media_scan_type and media_type are foreign-key parents -- media_scan points
 * at both -- so InnoDB rewrites the child's referenced names. dump is the
 * target of no constraint but carries two of its own, to media and users, and
 * those survive the rename.
 *
 * The three comment tables are the one place in this campaign where the model
 * is treated as more right than the table, which is the reverse of the rule
 * everywhere else. article_comments, interview_comments and review_comments
 * describe the *content* commented on; ScreenshotArticleComment and its two
 * siblings describe the *screenshot*, which is what the row actually is -- a
 * comment on a screenshot_article row, not on an article. The rule's
 * alternative here, keeping the table and renaming the model to
 * ArticleComment, would have the class state something false.
 *
 * Each comment table also carries a screenshot_*_id key to the pivot Unit 7
 * renames, and InnoDB rewrites those referenced names whichever unit deploys
 * first.
 *
 * See docs/plans/2026-08-29-plural-table-rename.md, Unit 6, and
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'media_scan'         => 'media_scans',
        'media_scan_type'    => 'media_scan_types',
        'media_type'         => 'media_types',
        'dump'               => 'dumps',
        'spotlight'          => 'spotlights',
        'article_comments'   => 'screenshot_article_comments',
        'interview_comments' => 'screenshot_interview_comments',
        'review_comments'    => 'screenshot_review_comments',
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
