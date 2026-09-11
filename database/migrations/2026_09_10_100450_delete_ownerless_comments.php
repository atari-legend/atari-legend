<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Delete the comments rows that belong to no game, article, interview or review.
 *
 * A comment reaches whatever it is on through one of four pivot tables, so a row
 * in none of them is unreachable: no page has ever rendered it, and
 * Comment::getTypeAttribute() throws 'Unknown comment type' on it rather than
 * displaying it. That is what lets
 * 2026_09_10_100500_comments_to_four_tables split comments into four tables
 * keyed on an owner -- a row with no owner has no table to go to.
 *
 * The predicate is the one that migration's guard counts, so the guard passes
 * once this has run and still refuses if it has not.
 *
 * The rows are listed before they go, because down() cannot put them back.
 * Restoring rows nothing could reach has no value, and 100500's own down()
 * does not recreate them either.
 *
 * See docs/plans/2026-09-10-relationship-cardinality.md, Unit 4.
 */
return new class extends Migration
{
    private const PIVOTS = ['game_comment', 'article_comment', 'interview_comment', 'review_comment'];

    public function up(): void
    {
        $owners = implode(' + ', array_map(
            fn ($pivot) => "(comments.id IN (SELECT comment_id FROM {$pivot}))",
            self::PIVOTS
        ));

        $ownerless = DB::table('comments')
            ->whereRaw("{$owners} = 0")
            ->get(['id', 'user_id', 'text', 'created_at']);

        if ($ownerless->isEmpty()) {
            return;
        }

        echo "\n";

        foreach ($ownerless as $comment) {
            echo "  comments {$comment->id}: by user {$comment->user_id} on {$comment->created_at}, "
                . '"' . str_replace("\n", ' ', mb_substr((string) $comment->text, 0, 60)) . "\"\n";
        }

        // By id, not by the predicate again: what goes is exactly what was
        // listed above.
        DB::table('comments')->whereIn('id', $ownerless->pluck('id'))->delete();

        echo '  Deleted ' . $ownerless->count() . " comments that belong to nothing.\n";
    }

    public function down(): void
    {
        // Nothing to restore: see the note above.
    }
};
