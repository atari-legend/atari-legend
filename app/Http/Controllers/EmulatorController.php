<?php

namespace App\Http\Controllers;

use App\Helpers\ReleaseHelper;
use App\Models\Dump;
use App\Models\GameRelease;
use App\Models\Media;
use App\Models\MenuDisk;
use App\Models\MenuSet;
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
            'disks'    => $this->releaseDisks($release, $dump),
            'tosUrl'   => asset('storage/' . self::TOS),
            'boxscans' => ReleaseHelper::boxScans($release),
        ]);
    }

    public function menuDisk(MenuSet $set, MenuDisk $disk): View
    {
        abort_unless($disk->menu->menuSet()->is($set) && $disk->menuDiskDump !== null, 404);

        return view('menus.emulator', [
            'disk'   => $disk,
            'disks'  => $this->menuDisks($disk),
            'tosUrl' => asset('storage/' . self::TOS),
        ]);
    }

    /**
     * Get every dump of a release, for the disk buttons.
     *
     * @param \App\Models\GameRelease Release to get the dumps of.
     * @param \App\Models\Dump Dump the emulator starts on.
     * @return \Illuminate\Support\Collection Each dump's id, URL, label and whether it is the current one.
     */
    private function releaseDisks(GameRelease $release, Dump $current): Collection
    {
        return $release->medias->values()->flatMap(function (Media $media, int $index) use ($current) {
            $name = $media->label ?? 'Disk ' . ($index + 1);

            return $media->dumps->map(fn (Dump $dump) => [
                'id'      => $dump->getKey(),
                'url'     => $dump->download_url,
                // Several dumps of the same disk differ by their format
                'label'   => $media->dumps->count() > 1 ? $name . ' (' . strtoupper($dump->format) . ')' : $name,
                'current' => $dump->is($current),
            ]);
        });
    }

    /**
     * Get the dumped disks of the menu a disk is on, for the disk buttons.
     *
     * @param \App\Models\MenuDisk Disk the emulator starts on.
     * @return \Illuminate\Support\Collection Each disk's dump id, URL, label and whether it is the current one.
     */
    private function menuDisks(MenuDisk $current): Collection
    {
        $menu = $current->menu;

        return $menu->disks()
            ->has('menuDiskDump')
            ->with('menuDiskDump')
            ->orderBy('part')
            ->get()
            ->map(fn (MenuDisk $disk) => [
                'id'      => $disk->menuDiskDump->getKey(),
                'url'     => $disk->menuDiskDump->download_url,
                'label'   => $menu->label . $disk->label,
                'current' => $disk->is($current),
            ]);
    }
}
