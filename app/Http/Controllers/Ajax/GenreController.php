<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Genre;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function genres(Request $request)
    {
        $genres = Genre::select('name')
            ->orderBy('name')
            ->limit(10);

        if ($request->filled('q')) {
            $genres = $genres->where('name', 'like', '%'.$request->q.'%');
        }

        return response()->json($genres->get());
    }
}
