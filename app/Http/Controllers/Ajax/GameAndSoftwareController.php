<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameAndSoftwareController extends Controller
{
    const MAX = 10;

    public function gamesAndSoftware(Request $request)
    {
        $games = DB::table('game')
            ->select(
                'game_name as name',
                'game_id as id',
                DB::raw("'fa-gamepad' as icon"),
                DB::raw('CONCAT("' . route('games.show', ['']) . '/", game_id) as url')
            )
            ->orderBy('game_name')
            ->limit(GameAndSoftwareController::MAX);

        $akas = DB::table('game_aka')
            ->select(
                'aka_name as name',
                'game_id as id',
                DB::raw("'fa-gamepad' as icon"),
                DB::raw('CONCAT("' . route('games.show', ['']) . '/", game_id) as url')
            )
            ->orderBy('aka_name')
            ->limit(GameAndSoftwareController::MAX);

        $software = DB::table('menu_software')
            ->select(
                'name',
                'id',
                DB::raw("'fa-desktop' as icon"),
                DB::raw('CONCAT("' . route('menus.software', ['']) . '/", id) as url')
            )
            ->orderBy('name')
            ->limit(GameAndSoftwareController::MAX);

        if ($request->filled('q')) {
            $games = $games->where('game_name', 'like', '%' . $request->q . '%');
            $akas = $akas->where('aka_name', 'like', '%' . $request->q . '%');
            $software = $software->where('name', 'like', '%' . $request->q . '%');
        }

        $all = $games->get()
            ->merge($akas->get())
            ->merge($software->get())
            ->sortBy('name')
            ->values()
            ->take(GameAndSoftwareController::MAX);

        return response()->json($all);
    }
}
