<?php

namespace App\Http\Controllers;

use App\Helpers\ReleaseDescriptionHelper;
use App\Models\Release;
use Illuminate\Support\Str;

class GameReleaseController extends Controller
{
    const FA_MEDIA_TYPE_ICONS = [
        1 => 'far fa-save',
        2 => 'far fa-save',
        3 => 'fas fa-sd-card',
        4 => 'fas fa-cloud-download-alt',
    ];

    public function show(Release $release)
    {
        $boxscans = $release->boxscans
            ->filter(function ($boxscan) {
                return Str::startsWith($boxscan->type, 'Box');
            })
            ->map(function ($boxscan) use ($release) {
                return [
                    'release' => $release,
                    'boxscan' => asset('storage/images/game_release_scans/'.$boxscan->file),
                ];
            });

        return view('games.releases.show')
            ->with([
                'release'        => $release,
                'boxscans'       => $boxscans,
                'descriptions'   => ReleaseDescriptionHelper::descriptions($release),
                'mediaTypeIcons' => GameReleaseController::FA_MEDIA_TYPE_ICONS,
            ]);
    }
}
