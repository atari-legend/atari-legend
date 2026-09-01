<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename article_types.article_type to name.
 *
 * A lookup-table label named after its own table. Unlike the other nine
 * columns in this unit it is not row content, so it takes `name` -- the label
 * column every other lookup table uses (controls.name, emulators.name,
 * game_genres.name) -- rather than a bare content word.
 *
 * articles.article_type_id is a foreign key and does not move.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_types', fn (Blueprint $t) => $t->renameColumn('article_type', 'name'));
    }

    public function down(): void
    {
        Schema::table('article_types', fn (Blueprint $t) => $t->renameColumn('name', 'article_type'));
    }
};
