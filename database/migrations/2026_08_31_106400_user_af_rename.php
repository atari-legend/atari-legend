<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename users.user_af to atari_forum.
 *
 * The profile's Atari Forum link. `atari_forum` is the self-documenting word;
 * the request key and the form field stay the shorter name="af".
 *
 * users.userid is the login identifier, not a prefixed social column, and is
 * one of the custom authentication columns this campaign excludes; the
 * users.user_id foreign keys on other tables are a different token and do not
 * move either.
 *
 * Unit 11 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('user_af', 'atari_forum'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('atari_forum', 'user_af'));
    }
};
