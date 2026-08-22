<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename screenshot_review.screenshot_review_id to id.
 *
 * The pivot's own key, so Review::screenshots()'s withPivot() follows it while the belongsToMany arguments do not.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screenshot_review', function (Blueprint $table) {
            $table->renameColumn('screenshot_review_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('screenshot_review', function (Blueprint $table) {
            $table->renameColumn('id', 'screenshot_review_id');
        });
    }
};
