<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Release;
use App\Models\ReleaseTOSIncompatibility;
use Illuminate\Http\Request;

class ReleaseSystemTosController extends Controller
{
    public function destroy(Game $game, Release $release, ReleaseTOSIncompatibility $incompatibility)
    {
        $incompatibility->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Incompatible TOS',
            'sub_section_id'   => $incompatibility->getKey(),
            'sub_section_name' => $incompatibility->tos->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function store(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'tos'      => 'required|numeric|exists:tos,id',
            'language' => 'nullable|alpha|exists:language,id',
        ]);

        $incompatibility = ReleaseTOSIncompatibility::create([
            'tos_id'      => $request->tos,
            'language_id' => $request->language,
            'release_id'  => $release->getKey(),
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Incompatible TOS',
            'sub_section_id'   => $incompatibility->getKey(),
            'sub_section_name' => $incompatibility->tos->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
