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
        $q = $request->q;

        $games = DB::table('game')
            ->select('game_name', 'game_id')
            ->limit(GameController::MAX);

        $akas = DB::table('game_aka')
            ->select('aka_name as game_name', 'game_id')
            ->limit(GameController::MAX);

        if ($q !== null) {
            // instr() is the substring-position function MySQL and SQLite
            // spell the same way; LOCATE() is MySQL-only. Its arguments are
            // (haystack, needle), the reverse of LOCATE(). The term is bound
            // rather than pasted into the SQL, which it used to be.
            $games = $games->where('game_name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(game_name, ?)', [$q]);
            $akas = $akas->where('aka_name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(aka_name, ?)', [$q]);
        } else {
            $games->orderBy('game_name');
            $akas->orderBy('aka_name');
        }

        $all = $games->get()
            ->merge($akas->get())
            ->sortBy(function ($data) use ($q) {
                return strpos(Str::lower($data->game_name), Str::lower($q ?? ''));
            })
            ->values()
            ->take(GameController::MAX)
            // The URL is built here rather than by CONCAT() in the query, which
            // is MySQL-only and meant interpolating a route into the SQL.
            ->map(function ($data) {
                $data->url = route('games.show', $data->game_id);

                return $data;
            })
            ->values();

        return response()->json($all);
    }
}
