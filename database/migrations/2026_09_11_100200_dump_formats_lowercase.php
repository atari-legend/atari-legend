<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * dumps.format and menu_disk_dumps.format become lowercase.
 *
 * They were the only uppercase values in the schema, and Unit 2 has just given
 * screenshots.imgext lowercase st, msa, stx, raw and scp members - so without
 * this the same five formats are spelled two ways across three columns.
 *
 * The column holds a filename suffix: DumpHelper builds paths out of it, one of
 * them lowercasing the value to do so. The two places a user reads it want STX,
 * and they uppercase for display.
 *
 * The values are lowercased before the enum is rewritten, because the old enum
 * accepts both spellings case-insensitively while the new one would refuse the
 * old rows.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 3.
 */
return new class extends Migration
{
    private const FORMATS = ['stx', 'msa', 'raw', 'scp', 'st'];

    public function up(): void
    {
        $this->rewrite(self::FORMATS, 'LOWER');
    }

    public function down(): void
    {
        $this->rewrite(array_map('strtoupper', self::FORMATS), 'UPPER');
    }

    private function rewrite(array $members, string $sqlFunction): void
    {
        foreach (['dumps', 'menu_disk_dumps'] as $table) {
            DB::table($table)->update(['format' => DB::raw("{$sqlFunction}(`format`)")]);

            // SQLite already holds this column as an unconstrained varchar, so
            // the values above are all it needs - and reaching an enum there
            // means rebuilding the table, which re-orders the two foreign keys
            // dumps.media_id carries and lets the RESTRICT one fire before the
            // CASCADE one. The enum is a MariaDB guarantee.
            if (DB::connection()->getDriverName() === 'sqlite') {
                continue;
            }

            Schema::table($table, fn (Blueprint $t) => $t->enum('format', $members)->nullable()->change());
        }
    }
};
