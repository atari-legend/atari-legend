<?php

namespace App\Http\Controllers;

use App\Helpers\ReleaseHelper;
use App\Models\Dump;
use App\Models\GameRelease;
use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class EmulatorController extends Controller
{
    /** TOS the emulator boots, on the public disk. */
    const TOS = 'tos/tos104uk.img';

    public function show(GameRelease $release, Dump $dump): View
    {
        abort_unless($dump->media->release()->is($release), 404);

        return view('games.releases.emulator', [
            'release'  => $release,
            'dump'     => $dump,
            'disks'    => $this->disks($release),
            'tosUrl'   => asset('storage/' . self::TOS),
            'boxscans' => ReleaseHelper::boxScans($release),
        ]);
    }

    /**
     * Get every dump of a release, labelled for the disk buttons.
     *
     * @param \App\Models\GameRelease Release to get the dumps of.
     * @return \Illuminate\Support\Collection Each dump with its label.
     */
    private function disks(GameRelease $release): Collection
    {
        return $release->medias->values()->flatMap(function (Media $media, int $index) {
            $name = $media->label ?? 'Disk ' . ($index + 1);

            return $media->dumps->map(fn (Dump $disk) => [
                'dump'  => $disk,
                // Several dumps of the same disk differ by their format
                'label' => $media->dumps->count() > 1 ? $name . ' (' . strtoupper($disk->format) . ')' : $name,
            ]);
        });
    }
}
