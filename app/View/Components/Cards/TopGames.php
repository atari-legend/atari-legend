<?php

namespace App\View\Components\Cards;

use App\Models\Game;
use Illuminate\View\Component;

class TopGames extends Component
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
        $games = Game::select('game.*')
            ->selectRaw('avg(score) as avgScore, count(score) as numVotes')
            ->join('game_votes', 'game.game_id', '=', 'game_votes.game_id')
            ->groupBy('game.game_id')
            ->orderByDesc('avgScore')
            ->limit(10)
            ->get()
            ->flatten();

        return view('components.cards.top-games')
            ->with([
                'games'     => $games,
            ]);
    }
}
