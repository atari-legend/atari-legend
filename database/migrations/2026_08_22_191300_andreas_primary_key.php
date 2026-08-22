<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename andreas.comments_id to id.
 *
 * Shares a column name with comments.comments_id but is an independent primary key on an unrelated table, so it renames on its own.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('andreas', function (Blueprint $table) {
            $table->renameColumn('comments_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('andreas', function (Blueprint $table) {
            $table->renameColumn('id', 'comments_id');
        });
    }
};
