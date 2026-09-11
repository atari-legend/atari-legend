<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Delete the menu_disk_dumps rows that belong to no menu disk.
 *
 * MenuDisksController::destroy() deleted a disk and left its dump behind, so a
 * dump could outlive the disk it described. menu_disks is the only way in to a
 * dump, so such a row is unreachable: no page renders it, no download links to
 * it, and `menus:check-dumps` can only label it 'Unknown'. That is what lets
 * 2026_09_10_100400_menu_disk_dump_id_to_child make menu_disk_id NOT NULL.
 *
 * The predicate is the one that migration's guard counts, so the guard passes
 * once this has run and still refuses if it has not.
 *
 * The rows are listed before they go, because down() cannot put them back.
 * Restoring rows nothing could reach has no value, and 100400's own down()
 * does not recreate them either.
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
            ->get(['id', 'format', 'sha512', 'created_at']);

        if ($orphans->isEmpty()) {
            return;
        }

        echo "\n";

        foreach ($orphans as $dump) {
            echo "  menu_disk_dumps {$dump->id}: {$dump->format}, uploaded {$dump->created_at}, sha512 "
                . substr((string) $dump->sha512, 0, 12) . "\n";
        }

        // By id, not by the predicate again: what goes is exactly what was
        // listed above.
        DB::table('menu_disk_dumps')->whereIn('id', $orphans->pluck('id'))->delete();

        echo '  Deleted ' . $orphans->count() . " menu_disk_dumps rows with no menu disk.\n";
    }

    public function down(): void
    {
        // Nothing to restore: see the note above.
    }
};
