<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interviews.interview_date to date.
 *
 * The publication date, still an int(11) unix timestamp with a
 * datetime:timestamp cast on Interview.
 *
 * Unit 9 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('interview_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('date', 'interview_date'));
    }
};
