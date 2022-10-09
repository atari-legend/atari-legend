<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->limit(GameAndSoftwareController::MAX);

        $akas = DB::table('game_aka')
            ->select(
                'aka_name as name',
                'game_id as id',
                DB::raw("'fa-gamepad' as icon"),
                DB::raw('CONCAT("' . route('games.show', ['']) . '/", game_id) as url')
            )
            ->limit(GameAndSoftwareController::MAX);

        $software = DB::table('menu_software')
            ->select(
                'name',
                'id',
                DB::raw("'fa-desktop' as icon"),
                DB::raw('CONCAT("' . route('menus.software', ['']) . '/", id) as url')
            )
            ->limit(GameAndSoftwareController::MAX);

        $q = $request->q;
        if ($q !== null) {
            $games = $games->where('game_name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', game_name)")
                ->orderbyRaw("CHAR_LENGTH('game_name')")
                ->orderBy('game_name');
            $akas = $akas->where('aka_name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', aka_name)")
                ->orderbyRaw("CHAR_LENGTH('aka_name')")
                ->orderBy('aka_name');
            $software = $software->where('name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', name)")
                ->orderbyRaw("CHAR_LENGTH('name')")
                ->orderBy('name');
        } else {
            $games = $games->orderBy('game_name');
            $akas = $akas->orderBy('aka_name');
            $software = $software->orderBy('name');
        }

        $all = $games->get()
            ->merge($akas->get())
            ->merge($software->get())
            ->sortBy([
                fn ($a, $b) => strpos(Str::lower($a->name), Str::lower($q)) <=> strpos(Str::lower($b->name), Str::lower($q)),
                fn ($a, $b) => strlen($a->name) <=> strlen($b->name),
                fn ($a, $b) => $a->name <=> $b->name,
            ])
            ->values()
            ->take(GameAndSoftwareController::MAX);

        return response()->json($all);
    }
}
