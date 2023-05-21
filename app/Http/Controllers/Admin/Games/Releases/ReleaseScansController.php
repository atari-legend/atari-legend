<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Helpers\ReleaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Release;
use App\Models\ReleaseScan;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ReleaseScansController extends Controller
{
    public function index(Game $game, Release $release)
    {
        return view('admin.games.games.releases.scans.index')
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
                        route('admin.games.releases.scans.index', ['game' => $release->game, 'release' => $release]),
                        'Scans'
                    ),
                ],
                'game'           => $release->game,
                'release'        => $release,
            ]);
    }

    public function update(Game $game, Release $release, ReleaseScan $scan, Request $request)
    {
        $scan->type = $request->type;
        $scan->notes = $request->notes;
        $scan->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Scan',
            'sub_section_id'   => $scan->getKey(),
            'sub_section_name' => $scan->type,
        ]);

        return redirect()->route('admin.games.releases.scans.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function destroy(Game $game, Release $release, ReleaseScan $scan, Request $request)
    {
        $scan->delete();
        Storage::disk('public')->delete($scan->path);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game Release',
            'section_id'       => $release->getKey(),
            'section_name'     => $release->game->game_name,
            'sub_section'      => 'Scan',
            'sub_section_id'   => $scan->getKey(),
            'sub_section_name' => $scan->type,
        ]);

        return redirect()->route('admin.games.releases.scans.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }

    public function store(Game $game, Release $release, Request $request)
    {
        $filepond = app(\Sopamo\LaravelFilepond\Filepond::class);

        foreach ($request->file as $file) {
            if ($file === null) {
                continue;
            }

            $path = $filepond->getPathFromServerId($file);
            $fullpath = Storage::path($path);
            $ext = File::extension($fullpath);

            $scan = ReleaseScan::create([
                'game_release_id' => $release->getKey(),
                'imgext'          => $ext,
                'type'            => ReleaseScan::TYPE_OTHER,
            ]);

            Storage::disk('public')->put($scan->path, Storage::get($path));

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Game Release',
                'section_id'       => $release->getKey(),
                'section_name'     => $release->game->game_name,
                'sub_section'      => 'Scan',
                'sub_section_id'   => $scan->getKey(),
                'sub_section_name' => $scan->type,
            ]);
        }

        return redirect()->route('admin.games.releases.scans.index', [
            'game'    => $release->game,
            'release' => $release,
        ]);
    }
}
