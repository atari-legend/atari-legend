<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class StatisticsHelper
{
    /**
     * Compute statistics on the database.
     *
     * @return array Map of statistics, with each stat description as the key
     */
    public static function getStatistics()
    {
        return [
            'Games'                         => DB::table('game')->count(),
            'Releases'                      => DB::table('game_release')->count(),
            'Screenshots'                   => DB::table('screenshot_game')->count(),
            'Games with screenshots'        => DB::table('screenshot_game')->distinct('game_id')->count(),
            'Companies'                     => DB::table('pub_dev')->count(),
            'Games with companies assigned' => DB::table('game_release')->distinct('id')->count(),
            'Games with developer assigned' => DB::table('game_developer')->distinct('game_id')->count(),
            'Games with box scans'          => DB::table('game_boxscan')->distinct('game_id')->count(),
            'Games with genre assigned'     => DB::table('game_genre_cross')->distinct('game_id')->count(),
            'Games reviewed'                => DB::table('review_game')->distinct('game_id')->count(),
            'Registered users'              => DB::table('users')->count(),
            'Articles'                      => DB::table('article_main')->count(),
            'Links'                         => DB::table('website')->count(),
            'Games with music assigned'     => DB::table('game_music')->distinct('game_id')->count(),
            'Music files'                   => DB::table('game_music')->count(),

        ];
    }
}
