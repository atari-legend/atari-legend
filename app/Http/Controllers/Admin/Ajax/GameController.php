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
            $games = $games->where('game_name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', game_name)")
                ->orderbyRaw("CHAR_LENGTH('game_name')")
                ->orderBy('game_name');
            $akas = $akas->where('aka_name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', aka_name)")
                ->orderbyRaw("CHAR_LENGTH('aka_name')")
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
                    'game_id'    => $aka->game->getKey(),
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
                    'game_id'    => $game->getKey(),
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

        $data = collect($gameData)->merge(collect($akaData))
            ->sortBy([
                fn ($a, $b) => strpos(Str::lower($a['game_name']), Str::lower($request->q)) <=> strpos(Str::lower($b['game_name']), Str::lower($request->q)),
            ])
            ->map(function ($data) {
                return [
                    'game_name' => $data['game_name'] . $data['developers'],
                    'game_id'   => $data['game_id'],
                ];
            })
            ->take(GameController::MAX);

        return response()->json($data);
    }
}
