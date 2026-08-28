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
            $games = $games->where('game_name', 'like', '%' . $request->q . '%')
                ->orderByRaw('instr(game_name, ?)', [$request->q])
                ->orderByRaw('length(game_name)')
                ->orderBy('game_name');
            $akas = $akas->where('aka_name', 'like', '%' . $request->q . '%')
                ->orderByRaw('instr(aka_name, ?)', [$request->q])
                ->orderByRaw('length(aka_name)')
                ->orderBy('aka_name');
        } else {
            $games = $games->orderBy('game_name');
            $akas = $akas->orderBy('aka_name');
        }

        $akaData = $akas->get()
            ->map(function ($aka) {
                $developers = '';
                if ($aka->game?->developers?->isNotEmpty()) {
                    $developers = ' [' . $aka->game->developers->pluck('pub_dev_name')->join(', ') . ']';
                }

                return [
                    'game_name'  => $aka->aka_name,
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
                    $developers = ' [' . $game->developers->pluck('pub_dev_name')->join(', ') . ']';
                }

                return [
                    'game_name'  => $game->game_name,
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
                fn ($a, $b) => strpos(Str::lower($a['game_name']), $term) <=> strpos(Str::lower($b['game_name']), $term),
                fn ($a, $b) => strlen($a['game_name']) <=> strlen($b['game_name']),
                fn ($a, $b) => $a['game_name'] <=> $b['game_name'],
            ]);
        }

        $data = $data
            ->map(function ($data) {
                return [
                    'game_name' => $data['game_name'] . $data['developers'],
                    'id'        => $data['id'],
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
