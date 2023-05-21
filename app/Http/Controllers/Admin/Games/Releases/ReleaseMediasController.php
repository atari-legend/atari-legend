<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Media;
use App\Models\MediaScanType;
use App\Models\MediaType;
use App\Models\Release;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReleaseMediasController extends Controller
{
    public function index(Game $game, Release $release)
    {
        $mediaTypes = MediaType::orderBy('name')->get();
        $mediaScanTypes = MediaScanType::orderBy('name')->get();

        return view('admin.games.games.releases.medias.index')
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
                        route('admin.games.releases.medias.index', ['game' => $release->game, 'release' => $release]),
                        'Dumps'
                    ),
                ],
                'game'           => $game,
                'release'        => $release,
                'mediaTypes'     => $mediaTypes,
                'mediaScanTypes' => $mediaScanTypes,
            ]);
    }

    public function update(Game $game, Release $release, Media $media, Request $request)
    {
        if ($request->delete) {
            $this->destroy($media);
        } else {
            if ($request->type) {
                $type = MediaType::findOrFail($request->type);
                $media->type()->associate($type);
            } else {
                $media->type()->dissociate();
            }

            $media->label = $request->label;
            $media->save();

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Game Release',
                'section_id'       => $media->release->getKey(),
                'section_name'     => $media->release->game->game_name,
                'sub_section'      => 'Media',
                'sub_section_id'   => $media->getKey(),
                'sub_section_name' => $media->release->game->game_name,
            ]);
        }

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $media->release->game,
            'release' => $media->release,
        ]);
    }

    public function store(Game $game, Release $release)
    {
        $floppyType = MediaType::where('name', 'like', '%floppy%')->first();
        $media = new Media();
        $media->type()->associate($floppyType);
        $media->release()->associate($release);
        $media->save();

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
            'sub_section'      => 'Media',
            'sub_section_id'   => $media->getKey(),
            'sub_section_name' => $media->release->game->game_name,
        ]);

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function destroy(Media $media)
    {
        foreach ($media->dumps as $dump) {
            Storage::disk('public')->delete($dump->path);
        }

        foreach ($media->scans as $scan) {
            Storage::disk('public')->delete($scan->path);
        }

        $media->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $media->release->getKey(),
            'section_name'     => $media->release->game->game_name,
            'sub_section'      => 'Media',
            'sub_section_id'   => $media->getKey(),
            'sub_section_name' => $media->release->game->game_name,
        ]);
    }
}
