<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename pub_devs.pub_dev_imgext to imgext.
 *
 * The logo's file extension, on the schema's majority spelling.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('pub_dev_imgext', 'imgext'));
    }

    public function down(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('imgext', 'pub_dev_imgext'));
    }
};
