<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two menu columns, neither carrying an index or a foreign key, so both are
 * a plain RENAME COLUMN and no TableRenamer is needed -- this unit renames
 * no table.
 *
 * menu_disk_contents.order is fully reserved in MySQL and MariaDB, a hazard
 * for the next raw statement rather than a current defect: no query in the
 * application names it unquoted. `position` costs nothing to adopt and
 * removes the hazard. Laravel quotes identifiers in renameColumn(), which is
 * why the reserved word survived this long and why down() can put it back.
 *
 * menu_sets.menus_sort carries its own table's plural stem on a table that
 * is about menu sets -- the survey's C14. `sort_direction` says what the
 * asc/desc enum holds without naming a table.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_disk_contents', fn (Blueprint $t) => $t->renameColumn('order', 'position'));
        Schema::table('menu_sets', fn (Blueprint $t) => $t->renameColumn('menus_sort', 'sort_direction'));
    }

    public function down(): void
    {
        Schema::table('menu_sets', fn (Blueprint $t) => $t->renameColumn('sort_direction', 'menus_sort'));
        Schema::table('menu_disk_contents', fn (Blueprint $t) => $t->renameColumn('position', 'order'));
    }
};
