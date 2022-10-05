<?php

namespace App\Http\Controllers\Admin\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameAka;
use Illuminate\Http\Request;

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
                ->orderByRaw("LOCATE('" . $request->q . "', game_name)");
            $akas = $akas->where('aka_name', 'like', '%' . $request->q . '%')
                ->orderByRaw("LOCATE('" . $request->q . "', aka_name)");
        } else {
            $games = $games->orderBy('game_name');
            $akas = $akas->orderBy('aka_name');
        }

        $akaData = $akas->get()
            ->map(function ($aka) {
                $game_name = $aka->aka_name;
                if ($aka->game?->developers?->isNotEmpty()) {
                    $game_name .= ' [' . $aka->game->developers->pluck('pub_dev_name')->join(', ') . ']';
                }

                return [
                    'game_name' => $game_name,
                    'game_id'   => $aka->game->getKey(),
                ];
            })
            ->take(GameController::MAX)
            ->toArray();

        $gameData = $games->get()
            ->map(function ($game) {
                $game_name = $game->game_name;
                if ($game->developers->isNotEmpty()) {
                    $game_name .= ' [' . $game->developers->pluck('pub_dev_name')->join(', ') . ']';
                }

                return [
                    'game_name' => $game_name,
                    'game_id'   => $game->getKey(),
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
            ->take(GameController::MAX);

        return response()->json($data);
    }
}
