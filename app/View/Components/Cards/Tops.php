<?php

namespace App\View\Components\Cards;

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
            ->join('game_developer', 'game_developer.dev_pub_id', '=', 'pub_dev.pub_dev_id')
            ->selectRaw('count(game_id) as game_count, pub_dev_name, pub_dev_id')
            ->where('pub_dev.pub_dev_name', '<>', 'Non-Commercial')
            ->groupBy('pub_dev_id')
            ->orderBy('game_count', 'desc')
            ->limit(5)
            ->get();

        $publishers = DB::table('pub_dev')
            ->join('game_release', 'game_release.pub_dev_id', '=', 'pub_dev.pub_dev_id')
            ->selectRaw('count(id) as release_count, pub_dev_name, pub_dev.pub_dev_id')
            ->where('pub_dev.pub_dev_name', '<>', 'Non-Commercial')
            ->groupBy('pub_dev_id')
            ->orderBy('release_count', 'desc')
            ->limit(5)
            ->get();

        $genres = DB::table('game_genre')
            ->join('game_genre_cross', 'game_genre_cross.game_genre_id', '=', 'game_genre.id')
            ->selectRaw('count(game_id) as game_count, game_genre.name, game_genre.id')
            ->groupBy('game_genre.id')
            ->orderBy('game_count', 'desc')
            ->limit(5)
            ->get();

        $individuals = DB::table('individuals')
            ->join('game_individual', 'game_individual.individual_id', '=', 'individuals.ind_id')
            ->selectRaw('count(game_id) as game_count, individuals.ind_name, individuals.ind_id')
            ->groupBy('individuals.ind_id')
            ->orderBy('game_count', 'desc')
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
