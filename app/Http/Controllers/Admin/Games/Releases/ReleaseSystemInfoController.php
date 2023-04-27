<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Emulator;
use App\Models\Game;
use App\Models\Release;
use App\Models\Resolution;
use App\Models\System;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class ReleaseSystemInfoController extends Controller
{
    public function index(Game $game, Release $release)
    {
        $resolutions = Resolution::orderBy('name')->get();
        $systems = System::orderBy('name')->get();
        $emulators = Emulator::orderBy('name')->get();

        return view('admin.games.games.releases.system.index')
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
                        route('admin.games.releases.system.index', ['game' => $release->game, 'release' => $release]),
                        'System info'
                    ),
                ],
                'game'        => $game,
                'release'     => $release,
                'resolutions' => $resolutions,
                'systems'     => $systems,
                'emulators'   => $emulators,
            ]);
    }

    public function update(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'resolutions' => 'array',
            'systems'     => 'array',
            'emulators'   => 'array',
        ]);

        $release->update(['hd_installable' => $request->hd === 'true']);

        if ($release->resolutions->pluck('id') !== $request->resolutions) {
            $release->resolutions()->detach();
            if ($request->resolutions) {
                $release->resolutions()->saveMany(
                    collect($request->resolutions)
                        ->map(fn ($id) => Resolution::findOrFail($id))
                        ->all()
                );
            }
        }

        if ($release->systemIncompatibles->pluck('id') !== $request->systems) {
            $release->systemIncompatibles()->detach();
            if ($request->systems) {
                $release->systemIncompatibles()->saveMany(
                    collect($request->systems)
                        ->map(fn ($id) => System::findOrFail($id))
                        ->all()
                );
            }
        }

        if ($release->emulatorIncompatibles()->pluck('emulator.id') !== $request->emulators) {
            $release->emulatorIncompatibles()->detach();
            if ($request->emulators) {
                $release->emulatorIncompatibles()->saveMany(
                    collect($request->emulators)
                        ->map(fn ($id) => Emulator::findOrFail($id))
                        ->all()
                );
            }
        }

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Compatibility',
            'sub_section_id'   => $release->getKey(),
            'sub_section_name' => $release->game->game_name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
