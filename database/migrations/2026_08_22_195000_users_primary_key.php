<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename users.user_id to id.
 *
 * Every other table's user_id is a foreign key and keeps its name --
 * change_log, comments, review_main, interview_main, article_main, news,
 * website, dump, game_votes and game_submitinfo among them.
 *
 * Admin/Ajax/UserController serialises the model straight to JSON, so its
 * payload key moves with the column and the four data-autocomplete-id="user_id"
 * attributes on the content edit forms move in the same commit.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B step 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('user_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('id', 'user_id');
        });
    }
};
