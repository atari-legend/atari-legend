<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Invert the menu disk / dump link: the foreign key moves to the child as
 * menu_disk_dumps.menu_disk_id, NOT NULL and unique.
 *
 * Both sides already declare a one-to-one -- MenuDisk::menuDiskDump() is a
 * belongsTo and MenuDiskDump::menuDisk() a hasOne -- but the key sat on the
 * parent with no unique index, so nothing enforced it and a dump could outlive
 * the disk it belonged to.
 *
 * Inverting rather than merging the dump into menu_disks keeps every dump id,
 * and therefore every zips/menus/{id}.zip name, unchanged.
 *
 * A dump with no disk cannot be expressed afterwards and down() could not
 * restore a row this deleted, so up() refuses to run while any exists.
 *
 * See docs/plans/2026-09-10-relationship-cardinality.md, Unit 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orphans = DB::table('menu_disk_dumps')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('menu_disks')
                ->whereColumn('menu_disks.menu_disk_dump_id', 'menu_disk_dumps.id'))
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException("{$orphans} menu_disk_dumps rows have no menu disk; delete them before migrating.");
        }

        $shared = DB::table('menu_disks')
            ->whereNotNull('menu_disk_dump_id')
            ->select('menu_disk_dump_id')->groupBy('menu_disk_dump_id')
            ->havingRaw('COUNT(*) > 1')->count();

        if ($shared > 0) {
            throw new RuntimeException("{$shared} menu_disk_dumps rows are referenced by more than one disk.");
        }

        Schema::table('menu_disk_dumps', function (Blueprint $t) {
            $t->unsignedBigInteger('menu_disk_id')->nullable()->after('id');
        });

        DB::table('menu_disk_dumps')->update([
            'menu_disk_id' => DB::raw('(SELECT md.`id` FROM `menu_disks` md WHERE md.`menu_disk_dump_id` = `menu_disk_dumps`.`id`)'),
        ]);

        $missing = DB::table('menu_disk_dumps')->whereNull('menu_disk_id')->count();

        if ($missing > 0) {
            throw new RuntimeException("{$missing} dumps have no menu_disk_id after the backfill; refusing to drop the old column.");
        }

        // Unique, not a plain index: it is the one-to-one both relations
        // already declare, and every reader in app/ and resources/ reads
        // $disk->menuDiskDump as a single object.
        Schema::table('menu_disk_dumps', function (Blueprint $t) {
            $t->unsignedBigInteger('menu_disk_id')->nullable(false)->change();
            $t->unique('menu_disk_id');
            $t->foreign('menu_disk_id')->references('id')->on('menu_disks')->cascadeOnDelete();
        });

        // Last, and only once the data is provably in its new home: MariaDB
        // will not roll a DDL statement back, so the ordering is the safety.
        Schema::table('menu_disks', function (Blueprint $t) {
            $t->dropForeign(['menu_disk_dump_id']);
            $t->dropColumn('menu_disk_dump_id');
        });
    }

    public function down(): void
    {
        Schema::table('menu_disks', function (Blueprint $t) {
            $t->unsignedBigInteger('menu_disk_dump_id')->nullable()->after('menu_disk_condition_id');
            // No cascade: the constraint it had restricted instead.
            $t->foreign('menu_disk_dump_id')->references('id')->on('menu_disk_dumps');
        });

        // A disk with no dump comes back NULL, which is what it is today.
        DB::table('menu_disks')->update([
            'menu_disk_dump_id' => DB::raw('(SELECT d.`id` FROM `menu_disk_dumps` d WHERE d.`menu_disk_id` = `menu_disks`.`id`)'),
        ]);

        Schema::table('menu_disk_dumps', function (Blueprint $t) {
            $t->dropForeign(['menu_disk_id']);
            $t->dropUnique(['menu_disk_id']);
            $t->dropColumn('menu_disk_id');
        });
    }
};
