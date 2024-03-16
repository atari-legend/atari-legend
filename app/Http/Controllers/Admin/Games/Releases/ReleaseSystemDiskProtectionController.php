<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\DiskProtection;
use App\Models\Game;
use App\Models\Release;
use Illuminate\Http\Request;

class ReleaseSystemDiskProtectionController extends Controller
{
    public function destroy(Game $game, Release $release, DiskProtection $protection)
    {
        $release->diskProtections()->detach($protection);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Disk Protection',
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
            'disk_protection' => 'required|numeric|exists:disk_protection,id',
        ]);

        $protection = DiskProtection::findOrFail($request->disk_protection);
        $release->diskProtections()->attach($protection, [
            'notes' => $request->disk_protection_notes,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Disk Protection',
            'sub_section_id'   => $protection->getKey(),
            'sub_section_name' => $protection->name,
        ]);

        return redirect()->route('admin.games.releases.system.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
