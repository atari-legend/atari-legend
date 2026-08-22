<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename news_submission.news_submission_id to id.
 *
 * Nothing references this table.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_submission', function (Blueprint $table) {
            $table->renameColumn('news_submission_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('news_submission', function (Blueprint $table) {
            $table->renameColumn('id', 'news_submission_id');
        });
    }
};
