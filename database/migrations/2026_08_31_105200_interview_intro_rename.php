<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interviews.interview_intro to intro.
 *
 * The interview's standfirst.
 *
 * Unit 9 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('interview_intro', 'intro'));
    }

    public function down(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('intro', 'interview_intro'));
    }
};
