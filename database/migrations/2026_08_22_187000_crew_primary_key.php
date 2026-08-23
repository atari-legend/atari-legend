<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename crew.crew_id to id.
 *
 * Four pivots carry a crew_id column -- crew_menu_set, game_release_crew,
 * crew_individual and the self-referential sub_crew -- and every one of them
 * keeps its name: those are foreign keys, and so is every belongsToMany
 * argument naming them.
 *
 * This is also the first rename that changes an AJAX payload. Ajax/CrewController
 * serialises the model straight to JSON, so its `crew_id` key becomes `id`, and
 * the data-autocomplete-id on the sub-crew field has to move in the same commit
 * or the browser writes `undefined` into the hidden companion field and submits
 * it. See docs/plans/2026-08-17-primary-key-rename.md, Phase B step 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crew', function (Blueprint $table) {
            $table->renameColumn('crew_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('crew', function (Blueprint $table) {
            $table->renameColumn('id', 'crew_id');
        });
    }
};
