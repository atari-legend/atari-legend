<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateWorldEuropeIso2Code extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('location')
            ->where('type', 'Continent')
            ->where('continent_code', 'EU')
            ->update(['country_iso2' => 'eu']);

        DB::table('location')
            ->where('type', 'Continent')
            ->where('continent_code', null)
            ->update(['country_iso2' => 'un']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
