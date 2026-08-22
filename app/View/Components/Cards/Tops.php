<?php

namespace App\View\Components\Cards;

use App\Models\Release;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class Tops extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $developers = DB::table('pub_dev')
            ->join('game_developer', 'game_developer.dev_pub_id', '=', 'pub_dev.id')
            ->selectRaw('count(game_id) as game_count, pub_dev_name, pub_dev.id')
            ->where('pub_dev.pub_dev_name', '<>', Release::LICENSE_NON_COMMERCIAL)
            ->groupBy('pub_dev.id', 'pub_dev.pub_dev_name')
            ->orderBy('game_count', 'desc')
            ->orderBy('pub_dev_name')
            ->limit(5)
            ->get();

        $publishers = DB::table('pub_dev')
            ->join('game_release', 'game_release.pub_dev_id', '=', 'pub_dev.id')
            ->selectRaw('count(pub_dev.id) as release_count, pub_dev_name, pub_dev.id')
            ->where('pub_dev.pub_dev_name', '<>', Release::LICENSE_NON_COMMERCIAL)
            // game_release still has a `pub_dev_id` foreign key while pub_dev's
            // own key is now `id`, so both sides stay qualified: only MySQL
            // resolves a bare name against the select list.
            ->groupBy('pub_dev.id', 'pub_dev.pub_dev_name')
            ->orderBy('release_count', 'desc')
            ->orderBy('pub_dev_name')
            ->limit(5)
            ->get();

        $genres = DB::table('game_genre')
            ->join('game_genre_cross', 'game_genre_cross.game_genre_id', '=', 'game_genre.id')
            ->selectRaw('count(game_id) as game_count, game_genre.name, game_genre.id')
            ->groupBy('game_genre.id', 'game_genre.name')
            ->orderBy('game_count', 'desc')
            ->orderBy('name')
            ->limit(5)
            ->get();

        $individuals = DB::table('individuals')
            ->join('game_individual', 'game_individual.individual_id', '=', 'individuals.id')
            ->selectRaw('count(game_id) as game_count, individuals.ind_name, individuals.id')
            ->groupBy('individuals.id', 'individuals.ind_name')
            ->orderBy('game_count', 'desc')
            ->orderBy('ind_name')
            ->limit(5)
            ->get();

        return view('components.cards.tops')
            ->with([
                'developers'  => $developers,
                'publishers'  => $publishers,
                'genres'      => $genres,
                'individuals' => $individuals,
            ]);
    }
}
