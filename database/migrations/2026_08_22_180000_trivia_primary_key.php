<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename trivia.trivia_id to id — the first of the primary-key renames.
 *
 * `trivia` is deliberately first: nothing references it, so this proves the
 * recipe end to end without any inbound foreign key to reason about. See
 * docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 *
 * One renameColumn per migration, in its own blueprint: no migration in this
 * project runs in a transaction (Grammar::$transactions is false with no
 * driver override), so a migration that renames several columns and fails
 * part-way leaves partial state with nothing to unwind it. Separate blueprints
 * also keep renameColumn working on SQLite, which is what the unit suite runs
 * against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trivia', function (Blueprint $table) {
            $table->renameColumn('trivia_id', 'id');
        });
    }

    /**
     * Reversing this is the only thing standing between a bad rename and an
     * unrecoverable production state: the revert commit takes this file with
     * it, so the rollback has to run while the file is still on the server.
     * See "Deploying a rename" in the plan.
     */
    public function down(): void
    {
        Schema::table('trivia', function (Blueprint $table) {
            $table->renameColumn('id', 'trivia_id');
        });
    }
};
