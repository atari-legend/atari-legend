<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individual_nicks.individual_nicks_id to id.
 *
 * The one table in the phase with an inbound foreign key:
 * crew_individual.individual_nick_id points here. It needs no drop and re-add.
 * ALTER TABLE ... RENAME COLUMN rewrites every inbound foreign key definition,
 * named and unnamed, with the constraints staying live -- verified by the
 * primary-key campaign on a parent carrying sixteen of them.
 *
 * No model, relationship, Blade template, factory or seeder reads this column:
 * the table is model-less or reached only as a pivot, and a belongsToMany
 * derives its keys from the model names. The only files naming it are the
 * historical create_* migration and, where one exists, an older data
 * migration -- both of which run before this one in date order and are left
 * alone.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individual_nicks', fn (Blueprint $t) => $t->renameColumn('individual_nicks_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('individual_nicks', fn (Blueprint $t) => $t->renameColumn('id', 'individual_nicks_id'));
    }
};
