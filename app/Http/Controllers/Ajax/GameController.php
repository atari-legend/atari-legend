<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function games(Request $request)
    {
        $games = Game::select('game_name')
            ->orderBy('game_name')
            ->limit(10);

        if ($request->filled('q')) {
            $games = $games->where('game_name', 'like', '%'.$request->q.'%');
        }

        return response()->json($games->get());
    }
}
