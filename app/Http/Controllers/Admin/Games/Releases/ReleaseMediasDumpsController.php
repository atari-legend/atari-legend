<?php

namespace App\Http\Controllers\Admin\Games\Releases;

use App\Helpers\ChangelogHelper;
use App\Helpers\DumpHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Dump;
use App\Models\Game;
use App\Models\Media;
use App\Models\Release;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ReleaseMediasDumpsController extends Controller
{
    public function store(Game $game, Release $release, Media $media, Request $request)
    {
        $filepond = app(\Sopamo\LaravelFilepond\Filepond::class);

        foreach ($request->file as $file) {
            if ($file === null) {
                continue;
            }

            $path = $filepond->getPathFromServerId($file);

            $zip = new ZipArchive();

            // If it's a ZIP, process the content one by one and assume they
            // all are dumps
            if ($zip->open(Storage::path($path)) === true) {
                for ($i = 0; $i < $zip->count(); $i++) {
                    $filename = $zip->getNameIndex($i);
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    if (in_array(strtoupper($ext), Dump::FORMATS)) {
                        $tmp = sys_get_temp_dir() . "/{$filename}";
                        $data = $zip->getFromIndex($i);
                        file_put_contents($tmp, $data);

                        $this->storeDump($media, $tmp);
                        unlink($tmp);
                    }
                }
                $zip->close();
            } else {
                // If not a ZIP, it's a standalone dump
                $this->storeDump($media, Storage::path($path));
            }

            Storage::delete($path);
        }

        return redirect()->route('admin.games.releases.medias.index', [
            'game'    => $media->release->game,
            'release' => $media->release,
        ]);
    }

    /**
     * Store a single dump in the database + filesystem.
     *
     * @param  $media  \App\Models\Media Media to attach the dump to.
     * @param  $fullpath  string            Path of the dump.
     */
    private function storeDump(Media $media, string $fullpath)
    {
        $format = DumpHelper::detectFormat($fullpath);

        if (in_array($format, Dump::FORMATS)) {
            $dump = new Dump([
                'format' => $format,
                'sha512' => hash('sha512', file_get_contents($fullpath)),
                'date'   => Carbon::now()->timestamp,
                'size'   => filesize($fullpath),
            ]);

            $dump->media()->associate($media);
            $dump->user()->associate(Auth::user());
            $dump->save();

            DumpHelper::storeDump($dump, $fullpath);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Game Release',
                'section_id'       => $media->release->getKey(),
                'section_name'     => $media->release->game->game_name,
                'sub_section'      => 'Dump',
                'sub_section_id'   => $dump->getKey(),
                'sub_section_name' => $dump->format,
            ]);
        }
    }

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
