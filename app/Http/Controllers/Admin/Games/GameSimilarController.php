<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class GameSimilarController extends Controller
{
    public function index(Game $game)
    {
        return view('admin.games.games.similar.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-similar.index', $game), 'Similar'),
                ],
                'game'        => $game,
            ]);
    }

    public function store(Request $request, Game $game)
    {
        $similar = Game::find($request->similar);
        if ($similar && ! $game->similarGames->contains($similar)) {
            $game->similarGames()->attach($similar);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Games',
                'section_id'       => $game->getKey(),
                'section_name'     => $game->game_name,
                'sub_section'      => 'Similar',
                'sub_section_id'   => $similar->getKey(),
                'sub_section_name' => $similar->game_name,
            ]);
        }

        return redirect()->route('admin.games.game-similar.index', $game);
    }

    public function destroy(Game $game, Game $similar)
    {
        $game->similarGames()->detach($similar);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Similar',
            'sub_section_id'   => $similar->getKey(),
            'sub_section_name' => $similar->game_name,
        ]);

        return redirect()->route('admin.games.game-similar.index', $game);
    }
}
