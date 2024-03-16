<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Memory;
use App\Models\Release;
use Illuminate\Http\Request;

class ReleaseSystemMemoryController extends Controller
{
    public function update(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'minimum_memory'      => 'array',
            'incompatible_memory' => 'array',
        ]);

        if ($release->memoryMinimums->pluck('memory.id') !== $request->minimum_memory) {
            $release->memoryMinimums()->detach();
            if ($request->minimum_memory) {
                $release->memoryMinimums()->saveMany(
                    collect($request->minimum_memory)
                        ->map(fn ($id) => Memory::findOrFail($id))
                        ->all()
                );
            }

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Game Release',
                'section_id'       => $release->getKey(),
                'section_name'     => $release->game->game_name,
                'sub_section'      => 'Minimum Memory',
                'sub_section_id'   => $release->getKey(),
                'sub_section_name' => $release->game->game_name,
            ]);
        }

        if ($release->memoryIncompatibles()->pluck('memory.id') !== $request->incompatible_memory) {
            $release->memoryIncompatibles()->detach();
            if ($request->incompatible_memory) {
                $release->memoryIncompatibles()->saveMany(
                    collect($request->incompatible_memory)
                        ->map(fn ($id) => Memory::findOrFail($id))
                        ->all()
                );
            }

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Game Release',
                'section_id'       => $release->getKey(),
                'section_name'     => $release->game->game_name,
                'sub_section'      => 'Incompatible Memory',
                'sub_section_id'   => $release->getKey(),
                'sub_section_name' => $release->game->game_name,
            ]);
        }

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
