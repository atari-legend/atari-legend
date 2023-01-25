<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $f = fopen('database/migrations/2023_01_25_181321_lemon64_import_slugs.tsv', 'r');
        $headerSkipped = false;
        while (! feof($f)) {
            $line = fgets($f);
            if (! $headerSkipped) {
                // Skip header line
                $headerSkipped = true;
                continue;
            }

            $row = str_getcsv($line, "\t");
            $l64_id = $row[1];
            $l64_slug = $row[2];

            DB::table('game_vs')
                ->where('C64_id', '=', $l64_id)
                ->update([
                    'lemon64_slug' => $l64_slug,
                ]);
        }

        fclose($f);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('game_vs')
            ->update([
                'lemon64_slug' => null,
            ]);
    }
};
