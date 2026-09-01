<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename users.user_twitter to twitter.
 *
 * The profile's Twitter link.
 *
 * Unit 11 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('user_twitter', 'twitter'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('twitter', 'user_twitter'));
    }
};
