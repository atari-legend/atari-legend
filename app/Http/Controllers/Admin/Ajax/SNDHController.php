<?php

namespace App\Http\Controllers\Admin\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Sndh;
use Illuminate\Http\Request;

class SNDHController extends Controller
{
    public function sndh(Request $request)
    {
        $sndhs = Sndh::select('*')
            ->selectRaw('CONCAT(title, " [", id, "]") as display')
            ->orderBy('id')
            ->limit(10);

        if ($request->filled('q')) {
            $sndhs = $sndhs->where('title', 'like', '%'.$request->q.'%')
                ->orWhere('composer', 'like', '%'.$request->q.'%');
        }

        return response()->json($sndhs->get());
    }
}
