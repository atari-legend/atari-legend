<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $changes = Changelog::where('user_id', Auth::user()->user_id)
            ->orderBy('timestamp', 'desc')
            ->limit(15)
            ->get();

        $gamesCount = DB::table('game')->count();
        $releasesCount = DB::table('game_release')->count();
        $usersCount = DB::table('users')->count();
        $individualsCount = DB::table('individuals')->count();
        $companiesCount = DB::table('pub_dev')->count();

        return view('admin.home.index')
            ->with([
                'changes'          => $changes,
                'gamesCount'       => $gamesCount,
                'releasesCount'    => $releasesCount,
                'usersCount'       => $usersCount,
                'individualsCount' => $individualsCount,
                'companiesCount'   => $companiesCount,
            ]);
    }
}
