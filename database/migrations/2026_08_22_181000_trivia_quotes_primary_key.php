<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename trivia_quotes.trivia_quote_id to id.
 *
 * Nothing has a foreign key to this table, so like `trivia` before it this is
 * a rename and nothing else. See docs/plans/2026-08-17-primary-key-rename.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trivia_quotes', function (Blueprint $table) {
            $table->renameColumn('trivia_quote_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('trivia_quotes', function (Blueprint $table) {
            $table->renameColumn('id', 'trivia_quote_id');
        });
    }
};
