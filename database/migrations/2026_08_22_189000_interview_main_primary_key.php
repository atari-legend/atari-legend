<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interview_main.interview_id to id.
 *
 * interview_text.interview_id and the interview_user_comments pivot keep their names: they are foreign keys.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_main', function (Blueprint $table) {
            $table->renameColumn('interview_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_main', function (Blueprint $table) {
            $table->renameColumn('id', 'interview_id');
        });
    }
};
