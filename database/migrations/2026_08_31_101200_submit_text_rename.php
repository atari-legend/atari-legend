<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename game_submit_infos.submit_text to text.
 *
 * The submitter's note. A word-census finding: `submit` is the table's second
 * word, so the stem census never saw it.
 *
 * GameSubmitInfo declares no $fillable and its writes are direct attribute
 * assignment, so a missed site fails loudly as SQL error 1054 rather than
 * dropping silently.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_submit_infos', fn (Blueprint $t) => $t->renameColumn('submit_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('game_submit_infos', fn (Blueprint $t) => $t->renameColumn('text', 'submit_text'));
    }
};
