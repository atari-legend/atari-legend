<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interviews.interview_chapters to chapters.
 *
 * The [hotspotUrl] chapter links. The interviews.individual_id and user_id
 * foreign keys do not move.
 *
 * Unit 9 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('interview_chapters', 'chapters'));
    }

    public function down(): void
    {
        Schema::table('interviews', fn (Blueprint $t) => $t->renameColumn('chapters', 'interview_chapters'));
    }
};
