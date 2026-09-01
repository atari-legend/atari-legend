<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename trivia_quotes.trivia_quote to quote.
 *
 * The row content, named after its own table. `quote` is the bare content
 * word; the table stays named for what it holds.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trivia_quotes', fn (Blueprint $t) => $t->renameColumn('trivia_quote', 'quote'));
    }

    public function down(): void
    {
        Schema::table('trivia_quotes', fn (Blueprint $t) => $t->renameColumn('quote', 'trivia_quote'));
    }
};
