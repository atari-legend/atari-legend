<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Engine;
use Illuminate\Http\Request;

class EngineController extends Controller
{
    public function engines(Request $request)
    {
        $engines = Engine::select('name')
            ->orderBy('name')
            ->limit(10);

        if ($request->filled('q')) {
            $engines = $engines->where('name', 'like', '%' . $request->q . '%');
        }

        return response()->json($engines->get());
    }
}
