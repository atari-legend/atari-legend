<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individuals.ind_profile to profile.
 *
 * The person's biography. One of the two `*_profile` prefixes the
 * main-text merge plan deferred; the other is pub_devs.pub_dev_profile.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('ind_profile', 'profile'));
    }

    public function down(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('profile', 'ind_profile'));
    }
};
