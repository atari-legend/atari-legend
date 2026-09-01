<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename users.user_fb to facebook.
 *
 * The profile's Facebook link. `facebook` is the self-documenting word for
 * what the column holds; a bare `fb` would not say. The request key and the
 * form field stay name="facebook".
 *
 * Unit 11 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('user_fb', 'facebook'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('facebook', 'user_fb'));
    }
};
