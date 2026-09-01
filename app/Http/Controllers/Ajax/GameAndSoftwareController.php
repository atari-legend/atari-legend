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
        $q = $request->q;

        $games = DB::table('games')
            ->select('name', 'id', DB::raw("'fa-gamepad' as icon"))
            ->limit(GameAndSoftwareController::MAX);

        $akas = DB::table('game_akas')
            ->select('name', 'game_id as id', DB::raw("'fa-gamepad' as icon"))
            ->limit(GameAndSoftwareController::MAX);

        $software = DB::table('menu_software')
            ->select('name', 'id', DB::raw("'fa-desktop' as icon"))
            ->limit(GameAndSoftwareController::MAX);

        if ($q !== null) {
            // instr() and length() are spelled the same in MySQL and SQLite;
            // LOCATE() and CHAR_LENGTH() are MySQL-only. The term is bound
            // rather than pasted into the SQL, which it used to be.
            //
            // The length ordering used to read CHAR_LENGTH('game_name') - the
            // quotes made it measure the literal string, so it was the same
            // number for every row and sorted nothing. It now measures the
            // column, which is what the PHP sort below has always done.
            $games = $games->where('name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(name, ?)', [$q])
                ->orderByRaw('length(name)')
                ->orderBy('name');
            $akas = $akas->where('name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(name, ?)', [$q])
                ->orderByRaw('length(name)')
                ->orderBy('name');
            $software = $software->where('name', 'like', '%' . $q . '%')
                ->orderByRaw('instr(name, ?)', [$q])
                ->orderByRaw('length(name)')
                ->orderBy('name');
        } else {
            $games = $games->orderBy('name');
            $akas = $akas->orderBy('name');
            $software = $software->orderBy('name');
        }

        // The SQL above orders each group; this orders the groups against each
        // other, by the same three rules.
        //
        // Only when there is a term to rank against: with no term every title
        // matches at position 0, and sorting on that would drop through to the
        // length rule and undo the alphabetical order the query asked for.
        $all = $games->get()
            ->merge($akas->get())
            ->merge($software->get());

        if ($q !== null) {
            $term = Str::lower($q);

            $all = $all->sortBy([
                fn ($a, $b) => strpos(Str::lower($a->name), $term) <=> strpos(Str::lower($b->name), $term),
                fn ($a, $b) => strlen($a->name) <=> strlen($b->name),
                fn ($a, $b) => $a->name <=> $b->name,
            ]);
        }

        $all = $all
            ->values()
            ->take(GameAndSoftwareController::MAX)
            // Built here rather than by CONCAT() in the query, which is
            // MySQL-only and meant interpolating a route into the SQL.
            ->map(function ($data) {
                $data->url = $data->icon === 'fa-desktop'
                    ? route('menus.software', $data->id)
                    : route('games.show', $data->id);

                return $data;
            })
            ->values();

        return response()->json($all);
    }
}
