<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename users.user_website to website.
 *
 * The profile's own-site link. The controllers already read a bare
 * $request->website and the form field is already name="website", so only the
 * column side moves.
 *
 * Unit 11 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('user_website', 'website'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('website', 'user_website'));
    }
};
