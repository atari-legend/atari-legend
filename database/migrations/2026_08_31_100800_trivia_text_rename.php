<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename trivia.trivia_text to text.
 *
 * The row content, named after its own table. trivia keeps its singular table
 * name: the model derives it, since Str::pluralStudly leaves an uncountable
 * word alone.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trivia', fn (Blueprint $t) => $t->renameColumn('trivia_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('trivia', fn (Blueprint $t) => $t->renameColumn('text', 'trivia_text'));
    }
};
