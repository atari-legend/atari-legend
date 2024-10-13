<?php

namespace App\Http\Controllers;

use App\Helpers\ReleaseHelper;
use App\Models\Dump;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmulatorController extends Controller
{
    public function index(Release $release, Dump $dump): View
    {
        $boxscans = ReleaseHelper::boxScans($dump->media->release);

        return view('games.releases.emulator', [
            'release'        => $dump->media->release,
            'dump'     => $dump,
            'boxscans'       => $boxscans,
        ]);
    }
}
