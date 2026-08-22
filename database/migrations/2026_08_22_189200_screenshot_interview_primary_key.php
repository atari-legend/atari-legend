<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_interview.screenshot_interview_id to id.
 *
 * The pivot's own key. interview_id and screenshot_id on it are foreign keys and stay, as does the withPivot() naming this column.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_interview', function (Blueprint $table) {
            $table->renameColumn('screenshot_interview_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('screenshot_interview', function (Blueprint $table) {
            $table->renameColumn('id', 'screenshot_interview_id');
        });
    }
};
