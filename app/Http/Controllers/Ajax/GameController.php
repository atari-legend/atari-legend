<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameController extends Controller
{
    const MAX = 10;

    public function games(Request $request)
    {
        $games = DB::table('game')
            ->select(
                'game_name',
                'game_id',
                DB::raw('CONCAT("' . route('games.show', ['']) . '/", game_id) as url')
            )
            ->limit(GameController::MAX);

        $akas = DB::table('game_aka')
            ->select(
                'aka_name as game_name',
                'game_id',
                DB::raw('CONCAT("' . route('games.show', ['']) . '/", game_id) as url')
            )
            ->limit(GameController::MAX);

        $q = $request->q;
        if ($q !== null) {
            $games = $games->where('game_name', 'like', '%' . $q . '%')
                ->orderByRaw("LOCATE('" . $q . "', game_name)");
            $akas = $akas->where('aka_name', 'like', '%' . $q . '%')
                ->orderByRaw("LOCATE('" . $q . "', aka_name)");
        } else {
            $games->orderBy('game_name');
            $akas->orderBy('aka_name');
        }

        $all = $games->get()
            ->merge($akas->get())
            ->sortBy(function ($data) use ($q) {
                return strpos(Str::lower($data->game_name), Str::lower($q));
            })
            ->values()
            ->take(GameController::MAX);

        return response()->json($all);
    }
}
