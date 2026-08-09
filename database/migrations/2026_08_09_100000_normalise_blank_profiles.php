<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The columns that should hold NULL rather than a blank value.
     */
    const COLUMNS = [
        'individual_text' => ['ind_profile', 'ind_email', 'ind_imgext'],
        'pub_dev_text'    => ['pub_dev_profile', 'pub_dev_imgext'],
    ];

    /**
     * Run the migrations.
     *
     * individual_text and pub_dev_text have a row for nearly every individual
     * and company, but the profile is usually blank. The original import wrote
     * that blank as a run of tab characters rather than NULL or an empty string,
     * which makes "has a bio" impossible to ask for without knowing the trick:
     * of the 4,525 individual_text rows, 1,403 hold only whitespace and 2,565
     * hold an empty string, leaving 220 with an actual bio.
     *
     * The whitespace is stripped with CHAR() rather than an escape sequence so
     * this runs on SQLite as well as MySQL.
     */
    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $stripped = "TRIM(REPLACE(REPLACE(REPLACE({$column}, CHAR(9), ''), CHAR(10), ''), CHAR(13), ''))";

                DB::table($table)
                    ->whereNotNull($column)
                    ->whereRaw("{$stripped} = ''")
                    ->update([$column => null]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to undo: the values replaced here carried no information, and
        // whether a given row held tabs or an empty string is not worth keeping.
    }
};
