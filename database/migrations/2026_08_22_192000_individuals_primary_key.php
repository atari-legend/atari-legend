<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individuals.ind_id to id.
 *
 * Widely referenced, but almost every reference is a foreign key: individual_text.ind_id, interview_main.ind_id, the crew_individual and individual_nicks pivots, and game_individual.individual_id all keep their names. Ajax/IndividualController builds its JSON from an array literal, so the wire key stays ind_id and the six data-autocomplete-id attributes are untouched -- only the select's column list moves.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->renameColumn('ind_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('individuals', function (Blueprint $table) {
            $table->renameColumn('id', 'ind_id');
        });
    }
};
