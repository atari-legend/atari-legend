<?php

namespace App\View\Components\Cards;

use App\Models\GameRelease;
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
        $developers = DB::table('companies')
            ->join('game_developer', 'game_developer.company_id', '=', 'companies.id')
            ->selectRaw('count(game_id) as game_count, companies.name, companies.id')
            ->where('companies.name', '<>', GameRelease::LICENSE_NON_COMMERCIAL)
            ->groupBy('companies.id', 'companies.name')
            ->orderBy('game_count', 'desc')
            ->orderBy('companies.name')
            ->limit(5)
            ->get();

        $publishers = DB::table('companies')
            ->join('game_releases', 'game_releases.company_id', '=', 'companies.id')
            ->selectRaw('count(companies.id) as release_count, companies.name, companies.id')
            ->where('companies.name', '<>', GameRelease::LICENSE_NON_COMMERCIAL)
            // game_releases still has a `company_id` foreign key while companies'
            // own key is now `id`, so both sides stay qualified: only MySQL
            // resolves a bare name against the select list.
            ->groupBy('companies.id', 'companies.name')
            ->orderBy('release_count', 'desc')
            ->orderBy('companies.name')
            ->limit(5)
            ->get();

        $genres = DB::table('game_genres')
            ->join('game_genre_cross', 'game_genre_cross.game_genre_id', '=', 'game_genres.id')
            ->selectRaw('count(game_id) as game_count, game_genres.name, game_genres.id')
            ->groupBy('game_genres.id', 'game_genres.name')
            ->orderBy('game_count', 'desc')
            ->orderBy('name')
            ->limit(5)
            ->get();

        $individuals = DB::table('individuals')
            ->join('game_individual', 'game_individual.individual_id', '=', 'individuals.id')
            ->selectRaw('count(game_id) as game_count, individuals.name, individuals.id')
            ->groupBy('individuals.id', 'individuals.name')
            ->orderBy('game_count', 'desc')
            ->orderBy('individuals.name')
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
