<?php

namespace App\View\Components\Cards;

use App\Models\Game;
use Illuminate\Support\Facades\DB;
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
        $votes = DB::table('game_votes')
            ->selectRaw('game_id, avg(score) as avgScore, count(score) as numVotes')
            ->groupBy('game_id');

        $games = Game::select('game.*')
            ->addSelect('votes.avgScore', 'votes.numVotes')
            ->joinSub($votes, 'votes', 'votes.game_id', '=', 'game.id')
            ->orderByDesc('avgScore')
            ->orderByDesc('numVotes')
            ->orderBy('game_name')
            ->limit(10)
            ->get();

        return view('components.cards.top-games')
            ->with([
                'games'     => $games,
            ]);
    }
}
