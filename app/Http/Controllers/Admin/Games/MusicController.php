<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Sndh;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MusicController extends Controller
{
    public function index()
    {
        $matches = [];
        Game::doesntHave('sndhs')
            ->inRandomOrder()
            ->limit(50)
            ->get()
            ->each(function ($game) use (&$matches) {
                $sndhs = Sndh::select()
                    ->whereRaw('MATCH(title) AGAINST(?)', [$game->game_name])
                    ->get();
                array_push($matches, [
                    'game' => $game,
                    'sndhs' => $sndhs,
                ]);
            });

        return view('admin.games.music.index')
            ->with([
                'breadcrumbs'            => [
                    new Crumb('#', 'Games'),
                    new Crumb(route('admin.games.music'), 'music'),
                ],
                'matches' => $matches,
            ]);
    }

    public function associate(Request $request)
    {
        $associations = [];
        if ($request->filled('associations')) {
            foreach ($request->associations as $association) {
                [$game_id, $sndh_id] = explode(':', $association);
                $game = Game::find($game_id);
                $sndh = Sndh::find($sndh_id);
                $game->sndhs()->attach($sndh);

                $associations[] = $game->game_name.' → '.$sndh->id;
            }
        }

        $request->session()->flash('alert-success', 'Associated '.join("\n\n", $associations));

        return redirect()->route('admin.games.music');
    }
}
