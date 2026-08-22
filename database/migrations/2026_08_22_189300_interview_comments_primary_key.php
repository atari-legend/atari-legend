<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interview_comments.interview_comment_id to id.
 *
 * Holds the caption for an interview screenshot; screenshot_interview_id on it is the foreign key and stays.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_comments', function (Blueprint $table) {
            $table->renameColumn('interview_comment_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_comments', function (Blueprint $table) {
            $table->renameColumn('id', 'interview_comment_id');
        });
    }
};
