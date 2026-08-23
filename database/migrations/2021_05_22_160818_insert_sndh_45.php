<?php

use App\Console\Commands\GenerateSNDHJson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class InsertSndh45 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $songs = json_decode(file_get_contents(base_path(GenerateSNDHJson::SONGS_JSON_PATH) . '/songs-4.5.json'), true);
        foreach ($songs as $path => $song) {
            DB::table('sndhs')->insert([
                'id'              => $path,
                'title'           => $song['title'] ?? null,
                'composer'        => $song['composer'] ?? null,
                'ripper'          => $song['ripper'] ?? null,
                'subtunes'        => $song['subtunes'] ?? null,
                'default_subtune' => $song['defaultSubtune'] ?? null,
                'year'            => $song['year'] ?? null,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('sndhs')->delete();
    }
}
