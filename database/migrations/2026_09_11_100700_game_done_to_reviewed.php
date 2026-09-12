<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * game_submissions.game_done becomes `reviewed`, a real boolean.
 *
 * It was char(1) holding the strings '1' for reviewed (2,868 rows) and '2' for
 * new (5 rows), through constants whose values invert the flag they name, and
 * GameSubmissionsTable already rendered it as a BooleanColumn headed "Reviewed"
 * with a callback whose only job was turning '1' into true.
 *
 * The values invert with the name: '1' becomes true, '2' becomes false.
 * E2ESeeder wrote 'N', which matched neither constant and rendered as
 * not-reviewed by falling through the comparison; it becomes false, which is
 * what it meant.
 *
 * down() writes the two constants back. The seeder's 'N' is not restored as
 * 'N': no production row holds that value.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_submissions', fn (Blueprint $t) => $t->boolean('reviewed')->default(false)->after('game_done'));

        DB::table('game_submissions')->update(['reviewed' => DB::raw("`game_done` = '1'")]);

        Schema::table('game_submissions', fn (Blueprint $t) => $t->dropColumn('game_done'));
    }

    public function down(): void
    {
        Schema::table('game_submissions', fn (Blueprint $t) => $t->char('game_done', 1)->nullable()->after('reviewed'));

        DB::table('game_submissions')->update(['game_done' => DB::raw("CASE WHEN `reviewed` THEN '1' ELSE '2' END")]);

        Schema::table('game_submissions', fn (Blueprint $t) => $t->dropColumn('reviewed'));
    }
};
