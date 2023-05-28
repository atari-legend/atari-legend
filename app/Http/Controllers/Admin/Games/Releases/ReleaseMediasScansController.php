<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Media;
use App\Models\MediaScan;
use App\Models\MediaScanType;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ReleaseMediasScansController extends Controller
{
    public function store(Game $game, Release $release, Media $media, Request $request)
    {
        $filepond = app(\Sopamo\LaravelFilepond\Filepond::class);
        $otherType = MediaScanType::where('name', '=', MediaScanType::TYPE_OTHER)->first();

        foreach ($request->file as $file) {
            if ($file === null) {
                continue;
            }

            $path = $filepond->getPathFromServerId($file);
            $fullpath = Storage::path($path);
            $ext = File::extension($fullpath);

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
                'section_name'     => $media->release->game->game_name,
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

    public function destroy(Game $game, Release $release, Media $media, MediaScan $scan)
    {
        Storage::disk('public')->delete($scan->path);
        $scan->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
            'sub_section'      => 'Media Scan',
            'sub_section_id'   => $scan->getKey(),
            'sub_section_name' => $scan->type->name,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $scan->media->release->game,
            'release' => $scan->media->release,
        ]);
    }

    public function update(Game $game, Release $release, Media $media, MediaScan $scan, Request $request)
    {
        $scan->type()->associate(MediaScanType::findOrFail($request->type));
        $scan->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
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
