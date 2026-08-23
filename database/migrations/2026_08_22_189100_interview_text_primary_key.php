<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename interview_text.interview_text_id to id.
 *
 * Ships with the interview_main rename, for the same reason as the article pair: the two are joined in four places and this is what makes both sides expose an id. Phase A2 qualified those selects.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_text', function (Blueprint $table) {
            $table->renameColumn('interview_text_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('interview_text', function (Blueprint $table) {
            $table->renameColumn('id', 'interview_text_id');
        });
    }
};
