<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Release;
use App\Models\Trainer;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class ReleaseSceneController extends Controller
{
    public function index(Game $game, Release $release)
    {
        $trainers = Trainer::orderBy('name')->get();

        return view('admin.games.games.releases.scene.index')
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
                        route('admin.games.releases.scene.index', ['game' => $release->game, 'release' => $release]),
                        'Scene'
                    ),
                ],
                'game'     => $game,
                'release'  => $release,
                'trainers' => $trainers,
            ]);
    }

    public function update(Request $request, Game $game, Release $release)
    {
        $request->validate(['trainers' => 'array']);

        if ($release->trainers->pluck('id') !== $request->trainers) {
            $release->trainers()->detach();
            if ($request->trainers) {
                $release->trainers()->saveMany(
                    collect($request->trainers)
                        ->map(fn ($id) => Trainer::findOrFail($id))
                        ->all()
                );
            }

            $release->refresh();
            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Game Release',
                'section_id'       => $release->getKey(),
                'section_name'     => $release->game->game_name,
                'sub_section'      => 'Scene',
                'sub_section_id'   => $release->getKey(),
                'sub_section_name' => $release->trainers->pluck('name')->join(', '),
            ]);
        }

        return redirect()->route('admin.games.releases.scene.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
