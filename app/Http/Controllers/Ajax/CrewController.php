<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Crew;
use Illuminate\Http\Request;

class CrewController extends Controller
{
    public function crews(Request $request)
    {
        $crews = Crew::select('crew_name', 'crew_id')
            ->orderBy('crew_name')
            ->limit(10);

        if ($request->filled('q')) {
            $crews = $crews->where('crew_name', 'like', '%'.$request->q.'%');
        }

        return response()->json($crews->get());
    }
}
