<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Dump;
use App\Models\Game;
use App\Models\Media;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReleaseMediasDumpsController extends Controller
{
    public function destroy(Game $game, Release $release, Media $media, Dump $dump)
    {
        Storage::disk('public')->delete($dump->path);
        $dump->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
            'sub_section'      => 'Dump',
            'sub_section_id'   => $dump->getKey(),
            'sub_section_name' => $dump->format,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $dump->media->release->game,
            'release' => $dump->media->release,
        ]);
    }

    public function update(Game $game, Release $release, Media $media, Dump $dump, Request $request)
    {
        $dump->info = $request->info;
        $dump->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
            'sub_section'      => 'Dump',
            'sub_section_id'   => $dump->getKey(),
            'sub_section_name' => $dump->format,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $dump->media->release->game,
            'release' => $dump->media->release,
        ]);
    }
}
