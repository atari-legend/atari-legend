<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename pub_devs.pub_dev_profile to profile.
 *
 * The company profile, the second of the two `*_profile` prefixes the
 * main-text merge plan deferred.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('pub_dev_profile', 'profile'));
    }

    public function down(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('profile', 'pub_dev_profile'));
    }
};
