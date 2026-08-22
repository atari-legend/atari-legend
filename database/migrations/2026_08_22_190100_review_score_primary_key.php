<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename review_score.review_score_id to id.
 *
 * review_id on this table is the foreign key to review_main and stays.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_score', function (Blueprint $table) {
            $table->renameColumn('review_score_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('review_score', function (Blueprint $table) {
            $table->renameColumn('id', 'review_score_id');
        });
    }
};
