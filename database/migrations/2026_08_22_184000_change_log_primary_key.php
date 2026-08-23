<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename change_log.change_log_id to id.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_log', function (Blueprint $table) {
            $table->renameColumn('change_log_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('change_log', function (Blueprint $table) {
            $table->renameColumn('id', 'change_log_id');
        });
    }
};
