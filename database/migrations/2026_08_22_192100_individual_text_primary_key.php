<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individual_text.ind_text_id to id.
 *
 * ind_id on this table is the foreign key to individuals and stays -- it is also what getFileAttribute() builds the avatar filename from, which is why that read is not getKey().
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individual_text', function (Blueprint $table) {
            $table->renameColumn('ind_text_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('individual_text', function (Blueprint $table) {
            $table->renameColumn('id', 'ind_text_id');
        });
    }
};
