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
            ->selectRaw('CAST(YEAR(date) AS CHAR) as year')
            ->distinct('YEAR(date)')
            ->whereNotNull('date')
            ->where('date', '!=', 0)
            ->orderBy('year')
            ->limit(10);

        if ($request->filled('q')) {
            $years = $years->whereRaw("YEAR(date) like '$request->q%'");
        }

        return response()->json($years->get());
    }
}
