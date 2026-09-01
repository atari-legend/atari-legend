<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename websites.website_count to count.
 *
 * A legacy hit counter nothing reads. The dead-tables campaign examined it and
 * kept it for the data -- 95 distinct values, up to 7,405 -- so this unit
 * renames rather than drops on that decision. `count` is a weak name for what
 * it holds, but a drop is a dead-column decision, not a naming one.
 *
 * Unit 6 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', fn (Blueprint $t) => $t->renameColumn('website_count', 'count'));
    }

    public function down(): void
    {
        Schema::table('websites', fn (Blueprint $t) => $t->renameColumn('count', 'website_count'));
    }
};
