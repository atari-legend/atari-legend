<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_interview_comments.comment_text to text.
 *
 * A screenshot description, the interview half of the same word-census
 * finding. ScreenshotInterviewComment declares $fillable and
 * Admin/Interviews/InterviewsController writes through it, so the same
 * silent-drop hazard applies and the model change ships here.
 *
 * Unit 3 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_interview_comments', fn (Blueprint $t) => $t->renameColumn('comment_text', 'text'));
    }

    public function down(): void
    {
        Schema::table('screenshot_interview_comments', fn (Blueprint $t) => $t->renameColumn('text', 'comment_text'));
    }
};
