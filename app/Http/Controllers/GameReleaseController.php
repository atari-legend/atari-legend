<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Http\Request;

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
        return view('games.releases.show')
            ->with([
                'release'        => $release,
                'mediaTypeIcons' => GameReleaseController::FA_MEDIA_TYPE_ICONS,
            ]);
    }
}
