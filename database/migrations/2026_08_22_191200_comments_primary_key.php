<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename comments.comments_id to id.
 *
 * The four *_user_comments pivots carry comments_id or comment_id as their foreign key and keep it. Only the comments table's own key moves.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->renameColumn('comments_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->renameColumn('id', 'comments_id');
        });
    }
};
