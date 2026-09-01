<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individuals.ind_email to email.
 *
 * The person's contact address. users.email is a different table, so the
 * pair is not a collision -- one of the two candidates a reader will look for.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('ind_email', 'email'));
    }

    public function down(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('email', 'ind_email'));
    }
};
