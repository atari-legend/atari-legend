<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReleaseYearController extends Controller
{
    public function releaseYears(Request $request)
    {
        $years = DB::table('game_release')
            ->selectRaw('substr(date, 1, 4) as year')
            ->distinct()
            ->whereNotNull('date')
            ->where('date', '!=', 0)
            ->orderBy('year')
            ->limit(10);

        if ($request->filled('q')) {
            $years = $years->whereRaw('substr(date, 1, 4) like ?', [$request->input('q') . '%']);
        }

        return response()->json($years->get());
    }
}
