<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Sndh;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class GameMusicController extends Controller
{
    public function index(Game $game)
    {
        $sndhs = Sndh::select()
            ->whereRaw('MATCH(title) AGAINST(?)', [$game->game_name])
            ->whereNotIn('id', $game->sndhs->pluck('id'))
            ->get();

        return view('admin.games.games.music.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-music.index', $game), 'Music'),
                ],
                'game'        => $game,
                'sndhs'       => $sndhs,
            ]);
    }

    public function store(Request $request, Game $game)
    {
        $sndh = Sndh::find($request->sndh);
        if ($sndh) {
            $game->sndhs()->attach($sndh);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Games',
                'section_id'       => $game->getKey(),
                'section_name'     => $game->game_name,
                'sub_section'      => 'Music',
                'sub_section_id'   => 0,
                'sub_section_name' => $sndh->id,
            ]);
        }

        return redirect()->route('admin.games.game-music.index', $game);
    }

    public function destroy(Game $game, Sndh $sndh)
    {
        $game->sndhs()->detach($sndh);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Music',
            'sub_section_id'   => 0,
            'sub_section_name' => $sndh->id,
        ]);

        return redirect()->route('admin.games.game-music.index', $game);
    }

    public function associate(Request $request, Game $game)
    {
        if ($request->filled('associations')) {
            foreach ($request->associations as $association) {
                $sndh = Sndh::find($association);
                $game->sndhs()->attach($sndh);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Games',
                    'section_id'       => $game->getKey(),
                    'section_name'     => $game->game_name,
                    'sub_section'      => 'Music',
                    'sub_section_id'   => 0,
                    'sub_section_name' => $sndh->id,
                ]);
            }
        }

        return redirect()->route('admin.games.game-music.index', $game);
    }
}
