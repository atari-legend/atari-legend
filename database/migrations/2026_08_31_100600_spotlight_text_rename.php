<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename spotlights.spotlight to text.
 *
 * The row content, named after its own table. It takes the bare content word
 * the parallel content columns take in Units 7-10.
 *
 * The token survives the rename in 72 lines that are not the column: the
 * $spotlight route-model-binding parameter and variable, the Spotlight model,
 * the spotlights table, the spotlight_screens storage folder key and the route
 * names. The form field stays name="spotlight" and so does the request key.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spotlights', fn (Blueprint $t) => $t->renameColumn('spotlight', 'text'));
    }

    public function down(): void
    {
        Schema::table('spotlights', fn (Blueprint $t) => $t->renameColumn('text', 'spotlight'));
    }
};
