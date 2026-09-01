<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename pub_devs.pub_dev_name to name.
 *
 * The company's display name.
 *
 * The raw-SQL sites are the campaign's worst case and all move here:
 * View/Components/Cards/Tops selectRaw()s the column beside a bare
 * orderBy() three times over, and AdminStatisticsHelper::topPublishers() and
 * topDevelopers() each select pub_devs.pub_dev_name and then read
 * $rows->pluck('total', 'pub_dev_name') -- the pluck names the select's output
 * column, not a table column, and both halves move together or the chart loses
 * its labels.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('pub_dev_name', 'name'));
    }

    public function down(): void
    {
        Schema::table('pub_devs', fn (Blueprint $t) => $t->renameColumn('name', 'pub_dev_name'));
    }
};
