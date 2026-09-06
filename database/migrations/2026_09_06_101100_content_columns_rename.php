<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Four content columns, none carrying an index or a foreign key, so all
 * four are a plain RENAME COLUMN and no TableRenamer is needed.
 *
 * reviews.edit is the survey's C14 and the most misleading name in the
 * schema: not an edit flag, but the "this is a user submission awaiting
 * approval" flag, read through Review::REVIEW_PUBLISHED /
 * REVIEW_UNPUBLISHED. The form field is already name="submission" with a
 * "Submission" label, so the rename aligns the column with the field and
 * label it already has. reviews.draft is a separate flag -- it hides a
 * published review from the site -- and both stay.
 *
 * trivia_quotes.quote is a self-named column; its sibling `trivia` already
 * calls the same thing `text`, and the admin form field is already
 * name="text" -- the same shape as spotlights.spotlight -> text and
 * comments.comment -> text before it.
 *
 * dumps.info becomes notes, matching game_releases.notes, menu_disks.notes,
 * game_release_scans.notes and the two protection pivots' notes. Its own
 * admin form field, name="info", moves with it -- unlike the unrelated
 * game-submission form, which happens to post a field of the same name to
 * a different table (game_submissions.text) and is not touched here.
 *
 * andreas.user_name is a typed-in guestbook name, not a reference to
 * `users`.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 11.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('edit', 'submission'));
        Schema::table('trivia_quotes', fn (Blueprint $t) => $t->renameColumn('quote', 'text'));
        Schema::table('dumps', fn (Blueprint $t) => $t->renameColumn('info', 'notes'));
        Schema::table('andreas', fn (Blueprint $t) => $t->renameColumn('user_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('andreas', fn (Blueprint $t) => $t->renameColumn('name', 'user_name'));
        Schema::table('dumps', fn (Blueprint $t) => $t->renameColumn('notes', 'info'));
        Schema::table('trivia_quotes', fn (Blueprint $t) => $t->renameColumn('text', 'quote'));
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('submission', 'edit'));
    }
};
