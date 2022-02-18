<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\MenuSoftware;
use Illuminate\Http\Request;

class SoftwareController extends Controller
{
    public function software(Request $request)
    {
        $software = MenuSoftware::select('name', 'id')
            ->orderBy('name')
            ->limit(10);

        if ($request->filled('q')) {
            $software = $software->where('name', 'like', '%' . $request->q . '%');
        }

        return response()->json($software->get());
    }
}
