<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename comments.comment to text.
 *
 * The row content, named after its own table.
 *
 * The high-noise token of the campaign: 368 lines name it and almost none are
 * the column. The Comment model, the comments table, the form field
 * name="comment", the request key, the route names (games.comment,
 * review.comment, ...), the data-comment-* JS hooks, the $comment variable and
 * the English word all stay. So does andreas.comment, a different table: the
 * proper-noun guestbook is not a self-named column.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', fn (Blueprint $t) => $t->renameColumn('comment', 'text'));
    }

    public function down(): void
    {
        Schema::table('comments', fn (Blueprint $t) => $t->renameColumn('text', 'comment'));
    }
};
