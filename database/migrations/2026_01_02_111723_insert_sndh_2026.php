<?php

use App\Console\Commands\GenerateSNDHJson;
use App\Models\Sndh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sndh_archives')->insert([
            'id'           => 'sndh2026lf',
            'download_url' => 'https://sndh.atari.org/files/sndh2026_lf.zip',
        ]);

        $songs = json_decode(file_get_contents(base_path(GenerateSNDHJson::SONGS_JSON_PATH) . '/songs-sndh2026lf.json'), true);
        foreach ($songs as $path => $song) {
            Sndh::updateOrCreate([
                'id'              => $path,
            ], [
                'sndh_archive_id' => 'sndh2026lf',
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
     */
    public function down(): void
    {
        DB::table('sndhs')
            ->where('sndh_archive_id', 'sndh2026lf')
            ->delete();

        DB::table('sndh_archives')
            ->where('id', 'sndh2026lf')
            ->delete();
    }
};
