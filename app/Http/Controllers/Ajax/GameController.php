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

        $games = DB::table('games')
            ->select('game_name', 'id as game_id')
            ->limit(GameController::MAX);

        $akas = DB::table('game_akas')
            ->select('aka_name as game_name', 'game_id')
            ->limit(GameController::MAX);

        if ($q !== null) {
            // Ordered the same way as GameAndSoftwareController: earliest
            // match first, then shortest title, then alphabetically. Both
            // endpoints feed a search box, so they should rank the same
            // titles the same way.
            //
            // instr() and length() are spelled the same in MySQL and SQLite;
            // LOCATE() and CHAR_LENGTH() are MySQL-only. instr() takes
            // (haystack, needle), the reverse of LOCATE(). The term is bound
            // rather than pasted into the SQL, which it used to be.
            $games = $games->where('game_name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(game_name, ?)', [$q])
                ->orderByRaw('length(game_name)')
                ->orderBy('game_name');
            $akas = $akas->where('aka_name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(aka_name, ?)', [$q])
                ->orderByRaw('length(aka_name)')
                ->orderBy('aka_name');
        } else {
            $games->orderBy('game_name');
            $akas->orderBy('aka_name');
        }

        // The SQL above orders each half; this orders the two halves against
        // each other, by the same three rules.
        //
        // Only when there is a term to rank against: with no term every title
        // matches at position 0, and sorting on that would drop through to the
        // length rule and undo the alphabetical order the query asked for.
        $all = $games->get()->merge($akas->get());

        if ($q !== null) {
            $term = Str::lower($q);

            $all = $all->sortBy([
                fn ($a, $b) => strpos(Str::lower($a->game_name), $term) <=> strpos(Str::lower($b->game_name), $term),
                fn ($a, $b) => strlen($a->game_name) <=> strlen($b->game_name),
                fn ($a, $b) => $a->game_name <=> $b->game_name,
            ]);
        }

        $all = $all
            ->values()
            ->take(GameController::MAX)
            // The URL is built here rather than by CONCAT() in the query, which
            // is MySQL-only and meant interpolating a route into the SQL.
            ->map(function ($data) {
                // Not getKey(): $data is a stdClass row from DB::table(), not
                // an Eloquent model, so getKey() does not exist on it. This
                // column follows the schema and is renamed in Phase B, not by
                // the getKey() pre-pass.
                $data->url = route('games.show', $data->game_id);

                return $data;
            })
            ->values();

        return response()->json($all);
    }
}
