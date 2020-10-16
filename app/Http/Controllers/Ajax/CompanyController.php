<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\PublisherDeveloper;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function companies(Request $request)
    {
        $companies = PublisherDeveloper::select()
            ->orderBy('pub_dev_name')
            ->limit(10);

        if ($request->filled('q')) {
            $companies = $companies->where('pub_dev_name', 'like', '%'.$request->q.'%');
        }

        return response()->json($companies->get());
    }
}
