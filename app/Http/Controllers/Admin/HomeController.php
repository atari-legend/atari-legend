<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AdminStatisticsHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\View\Components\Admin\Crumb;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $changes = Changelog::where('user_id', Auth::user()->user_id)
            ->orderBy('timestamp', 'desc')
            ->limit(15)
            ->get();

        return view('admin.home.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.home.index'), 'Home'),
                ],
                'changes' => $changes,
                'stats'   => AdminStatisticsHelper::headlines(),
            ]);
    }
}
