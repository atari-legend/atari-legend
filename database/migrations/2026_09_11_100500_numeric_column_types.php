<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Six columns typed for something other than what they hold.
 *
 * dumps.sha512 and menu_disk_dumps.sha512 were varchar(128) while
 * users.sha512_password and users.salt are char(128). All four hold a hex
 * SHA-512 and every value in all four is exactly 128 characters, so the two
 * dump columns join the other two. (The plan names only dumps.sha512;
 * menu_disk_dumps.sha512 is the same value in the same wrong type, and leaving
 * it would make it the only varchar SHA-512 left.)
 *
 * dumps.size was int(50) and menu_disk_dumps.size int(11), both signed, both
 * holding a byte count. int(50) is a display width MySQL 8 deprecated and
 * MariaDB ignores, and neither column can hold a negative value. Measured
 * maxima are 1,901,002 and 935,254, against an int unsigned ceiling of
 * 4,294,967,295.
 *
 * menu_disk_contents.position was tinyint(4), signed, ceiling 127. This is the
 * one column here with a live failure mode: the largest position stored is 106,
 * on a disk that has 106 entries, so a menu disk with 128 of them fails to
 * insert its last row under strict mode.
 *
 * users.permission was int(2), holding 1 or 2 across 767 of 771 rows.
 * User::PERMISSION_ADMIN and PERMISSION_USER keep their values, so every site
 * comparing them is unchanged.
 *
 * No value changes: every conversion either widens the range or is a no-op for
 * values that are already exactly 128 characters.
 *
 * Skipped on SQLite, where the suite runs: it has one INTEGER type and no
 * varchar length, so none of this is expressible there.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 6.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->skipped()) {
            return;
        }

        Schema::table('dumps', function (Blueprint $t) {
            $t->char('sha512', 128)->nullable()->change();
            $t->unsignedInteger('size')->nullable()->change();
        });

        Schema::table('menu_disk_dumps', function (Blueprint $t) {
            $t->char('sha512', 128)->nullable()->change();
            $t->unsignedInteger('size')->nullable()->change();
        });

        Schema::table('menu_disk_contents', fn (Blueprint $t) => $t->unsignedSmallInteger('position')->change());

        Schema::table('users', fn (Blueprint $t) => $t->unsignedTinyInteger('permission')->nullable()->change());
    }

    /**
     * Raw, because int(50) and int(2) are display widths the Blueprint cannot
     * express - it emits int(11) for every integer - and this is what those
     * columns were declared as.
     */
    public function down(): void
    {
        if ($this->skipped()) {
            return;
        }

        foreach ([
            'ALTER TABLE `dumps` MODIFY `sha512` VARCHAR(128) NULL',
            'ALTER TABLE `dumps` MODIFY `size` INT(50) NULL',
            'ALTER TABLE `menu_disk_dumps` MODIFY `sha512` VARCHAR(128) NULL',
            'ALTER TABLE `menu_disk_dumps` MODIFY `size` INT(11) NULL',
            'ALTER TABLE `menu_disk_contents` MODIFY `position` TINYINT(4) NOT NULL',
            'ALTER TABLE `users` MODIFY `permission` INT(2) NULL',
        ] as $statement) {
            DB::statement($statement);
        }
    }

    private function skipped(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
};
