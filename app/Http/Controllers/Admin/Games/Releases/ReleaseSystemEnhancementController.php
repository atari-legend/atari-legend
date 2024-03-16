<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Release;
use App\Models\ReleaseSystemEnhanced;
use Illuminate\Http\Request;

class ReleaseSystemEnhancementController extends Controller
{
    public function destroy(Game $game, Release $release, ReleaseSystemEnhanced $enhancement)
    {
        $enhancement->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'System Enhancement',
            'sub_section_id'   => $enhancement->getKey(),
            'sub_section_name' => $enhancement->system->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function store(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'enhancement' => 'nullable|numeric|exists:enhancement,id',
            'system'      => 'required|numeric|exists:system,id',
        ]);

        $enhancement = ReleaseSystemEnhanced::create([
            'enhancement_id'  => $request->enhancement,
            'system_id'       => $request->system,
            'game_release_id' => $release->getKey(),
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'System Enhancement',
            'sub_section_id'   => $enhancement->getKey(),
            'sub_section_name' => $enhancement->system->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
