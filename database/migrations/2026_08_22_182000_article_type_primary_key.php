<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename article_type.article_type_id to id.
 *
 * The first rename with an inbound foreign key: article_main.article_type_id
 * points here. That column is a *foreign* key and keeps its name -- only the
 * parent's own key moves. MariaDB rewrites the constraint to follow the rename
 * on its own, so there is no drop-and-re-add here.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B step 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_type', function (Blueprint $table) {
            $table->renameColumn('article_type_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('article_type', function (Blueprint $table) {
            $table->renameColumn('id', 'article_type_id');
        });
    }
};
