<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename bug_report_type.bug_report_type_id to id.
 *
 * Phase 3a constrains bug_report.bug_report_type_id against this column, so
 * that migration is written against the name this one leaves behind.
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
        Schema::table('bug_report_type', fn (Blueprint $t) => $t->renameColumn('bug_report_type_id', 'id'));
    }

    public function down(): void
    {
        Schema::table('bug_report_type', fn (Blueprint $t) => $t->renameColumn('id', 'bug_report_type_id'));
    }
};
