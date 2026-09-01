<?php

namespace App\Http\Controllers\Admin\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameAka;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    const MAX = 20;

    public function games(Request $request)
    {
        $games = Game::select('*')
            ->limit(GameController::MAX);
        $akas = GameAka::select('*')
            ->limit(GameController::MAX);

        if ($request->filled('q')) {
            // instr() and length() are spelled the same in MySQL and SQLite;
            // LOCATE() and CHAR_LENGTH() are MySQL-only. instr() takes
            // (haystack, needle), the reverse of LOCATE(), and the term is
            // bound rather than pasted into the SQL, which it used to be.
            //
            // The length ordering used to read CHAR_LENGTH('game_name'): the
            // quotes made it measure the literal string, the same number for
            // every row, so it sorted nothing.
            $games = $games->where('name', 'like', '%' . $request->q . '%')
                ->orderByRaw('instr(name, ?)', [$request->q])
                ->orderByRaw('length(name)')
                ->orderBy('name');
            $akas = $akas->where('name', 'like', '%' . $request->q . '%')
                ->orderByRaw('instr(name, ?)', [$request->q])
                ->orderByRaw('length(name)')
                ->orderBy('name');
        } else {
            $games = $games->orderBy('name');
            $akas = $akas->orderBy('name');
        }

        $akaData = $akas->get()
            ->map(function ($aka) {
                $developers = '';
                if ($aka->game?->developers?->isNotEmpty()) {
                    $developers = ' [' . $aka->game->developers->pluck('name')->join(', ') . ']';
                }

                return [
                    'name'       => $aka->name,
                    'developers' => $developers,
                    'id'         => $aka->game->getKey(),
                ];
            })
            ->take(GameController::MAX)
            ->toArray();

        $gameData = $games->get()
            ->map(function ($game) {
                $developers = '';
                if ($game->developers->isNotEmpty()) {
                    $developers = ' [' . $game->developers->pluck('name')->join(', ') . ']';
                }

                return [
                    'name'       => $game->name,
                    'developers' => $developers,
                    'id'         => $game->getKey(),
                ];
            })
            ->take(GameController::MAX)
            ->toArray();

        // toArray() then collect() is needed because we might have an
        // empty collection in one case (Illuminate\Support\Collection)
        // and an Eloquent collection in the other (Illuminate\Database\Eloquent\Collection)
        // The later overrides the merge() method and expects an Eloquent collection
        // too...
        // See: https://stackoverflow.com/a/67237117/582594

        $data = collect($gameData)->merge(collect($akaData));

        // Ranked the same way as the two public autocompletes: earliest match
        // first, then shortest title, then alphabetically. Only when there is
        // a term, otherwise every title matches at position 0 and the length
        // rule would undo the alphabetical order the query asked for.
        if ($request->filled('q')) {
            $term = Str::lower($request->q);

            $data = $data->sortBy([
                fn ($a, $b) => strpos(Str::lower($a['name']), $term) <=> strpos(Str::lower($b['name']), $term),
                fn ($a, $b) => strlen($a['name']) <=> strlen($b['name']),
                fn ($a, $b) => $a['name'] <=> $b['name'],
            ]);
        }

        $data = $data
            ->map(function ($data) {
                return [
                    'name' => $data['name'] . $data['developers'],
                    'id'   => $data['id'],
                ];
            })
            ->take(GameController::MAX)
            // sortBy() keeps the original keys, so any query the ranking
            // actually reorders would be serialised as a JSON object rather
            // than an array - and autoComplete.js iterates on .length, so the
            // dropdown silently comes back empty. The two public autocompletes
            // already do this.
            ->values();

        return response()->json($data);
    }
}
