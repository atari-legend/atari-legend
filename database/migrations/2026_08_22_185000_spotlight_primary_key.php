<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename spotlight.spotlight_id to id.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spotlight', function (Blueprint $table) {
            $table->renameColumn('spotlight_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('spotlight', function (Blueprint $table) {
            $table->renameColumn('id', 'spotlight_id');
        });
    }
};
