<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    const MAX = 10;

    public function games(Request $request)
    {
        $games = DB::table('game')
            ->select(
                'game_name',
                'game_id',
                DB::raw('CONCAT("'.route('games.show', ['']).'/", game_id) as url')
            )
            ->orderBy('game_name')
            ->limit(GameController::MAX);

        $akas = DB::table('game_aka')
            ->select(
                'aka_name as game_name',
                'game_id',
                DB::raw('CONCAT("'.route('games.show', ['']).'/", game_id) as url')
            )
            ->orderBy('aka_name')
            ->limit(GameController::MAX);

        if ($request->filled('q')) {
            $games = $games->where('game_name', 'like', '%'.$request->q.'%');
            $akas = $akas->where('aka_name', 'like', '%'.$request->q.'%');
        }

        $all = $games->get()
            ->merge($akas->get())
            ->sortBy('game_name')
            ->values()
            ->take(GameController::MAX);

        return response()->json($all);
    }
}
