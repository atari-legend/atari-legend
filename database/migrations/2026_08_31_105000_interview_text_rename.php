<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interviews.interview_text to text.
 *
 * The interview body. It keeps its `text` column type; only the name moves.
 *
 * Unit 9 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('interview_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('text', 'interview_text'));
    }
};
