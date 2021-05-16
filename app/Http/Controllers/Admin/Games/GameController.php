<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\View\Components\Admin\Crumb;

class GameController extends Controller
{
    public function index()
    {
        return view('admin.games.games.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                ],
            ]);
    }

    public function edit(Game $game)
    {
        return view('admin.games.games.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                ],
                'game'        => $game,
            ]);
    }
}
