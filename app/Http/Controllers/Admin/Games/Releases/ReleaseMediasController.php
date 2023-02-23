<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\MediaType;
use App\Models\Release;
use App\View\Components\Admin\Crumb;

class ReleaseMediasController extends Controller
{
    public function index(Game $game, Release $release)
    {
        $mediaTypes = MediaType::orderBy('name')->get();

        return view('admin.games.games.releases.medias.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $release->game), $release->game->game_name),
                    new Crumb(route('admin.games.releases.index', $release->game), 'Releases'),
                    new Crumb(
                        route('admin.games.releases.show', ['game' => $release->game, 'release' => $release]),
                        $release->full_label,
                        ReleaseHelper::siblingReleasesCrumbs($release, 'admin.games.releases.medias.index')
                    ),
                    new Crumb(
                        route('admin.games.releases.medias.index', ['game' => $release->game, 'release' => $release]),
                        'Dumps'
                    ),
                ],
                'game'        => $game,
                'release'     => $release,
                'mediaTypes'  => $mediaTypes,
            ]);
    }
}
