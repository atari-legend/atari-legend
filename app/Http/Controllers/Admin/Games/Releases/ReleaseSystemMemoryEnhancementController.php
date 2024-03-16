<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Release;
use App\Models\ReleaseMemoryEnhanced;
use Illuminate\Http\Request;

class ReleaseSystemMemoryEnhancementController extends Controller
{
    public function destroy(Game $game, Release $release, ReleaseMemoryEnhanced $enhancement)
    {
        $enhancement->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Memory Enhancement',
            'sub_section_id'   => $enhancement->getKey(),
            'sub_section_name' => $enhancement->memory->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function store(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'memory_enhancement' => 'nullable|numeric|exists:enhancement,id',
            'memory'             => 'required|numeric|exists:memory,id',
        ]);

        $enhancement = ReleaseMemoryEnhanced::create([
            'enhancement_id'  => $request->memory_enhancement,
            'memory_id'       => $request->memory,
            'release_id'      => $release->getKey(),
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Memory Enhancement',
            'sub_section_id'   => $enhancement->getKey(),
            'sub_section_name' => $enhancement->memory->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
