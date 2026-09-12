<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Media;
use App\Models\MediaScan;
use App\Models\MediaScanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReleaseMediasScansController extends Controller
{
    use ValidatesFilepondExtensions;

    public function store(Game $game, GameRelease $release, Media $media, Request $request)
    {
        $request->validate(['file' => 'required|array']);

        $otherType = MediaScanType::where('name', '=', MediaScanType::TYPE_OTHER)->first();

        foreach ($this->filepondExtensions($request->file, MediaScan::EXTENSIONS) as $path => $ext) {
            $scan = new MediaScan([
                'imgext' => $ext,
            ]);
            $scan->type()->associate($otherType);
            $scan->media()->associate($media);
            $scan->save();

            Storage::disk('public')->put($scan->path, Storage::get($path));
            Storage::delete($path);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Game Release',
                'section_id'       => $media->release->getKey(),
                'section_name'     => $media->release->game->name,
                'sub_section'      => 'Media Scan',
                'sub_section_id'   => $scan->getKey(),
                'sub_section_name' => $scan->type->name,
            ]);
        }

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $media->release->game,
            'release' => $media->release,
        ]);
    }

    public function destroy(Game $game, GameRelease $release, Media $media, MediaScan $scan)
    {
        Storage::disk('public')->delete($scan->path);
        $scan->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->name,
            'sub_section'      => 'Media Scan',
            'sub_section_id'   => $scan->getKey(),
            'sub_section_name' => $scan->type->name,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $scan->media->release->game,
            'release' => $scan->media->release,
        ]);
    }

    public function update(Game $game, GameRelease $release, Media $media, MediaScan $scan, Request $request)
    {
        $scan->type()->associate(MediaScanType::findOrFail($request->type));
        $scan->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->name,
            'sub_section'      => 'Media Scan',
            'sub_section_id'   => $scan->getKey(),
            'sub_section_name' => $scan->type->name,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $scan->media->release->game,
            'release' => $scan->media->release,
        ]);
    }
}
