<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\CopyProtection;
use App\Models\Game;
use App\Models\Release;
use Illuminate\Http\Request;

class ReleaseSystemCopyProtectionController extends Controller
{
    public function destroy(Game $game, Release $release, CopyProtection $protection)
    {
        $release->copyProtections()->detach($protection);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Copy Protection',
            'sub_section_id'   => $protection->getKey(),
            'sub_section_name' => $protection->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function store(Request $request, Game $game, Release $release)
    {
        $request->validate([
            'copy_protection' => 'required|numeric|exists:copy_protection,id',
        ]);

        $protection = CopyProtection::findOrFail($request->copy_protection);
        $release->copyProtections()->attach($protection, [
            'notes' => $request->copy_protection_notes,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Copy Protection',
            'sub_section_id'   => $protection->getKey(),
            'sub_section_name' => $protection->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
