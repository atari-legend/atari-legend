<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function companies(Request $request)
    {
        $companies = Company::select('companies.*')
            ->orderBy('name')
            ->limit(10);

        if ($request->filled('q')) {
            $companies = $companies->where('name', 'like', '%' . $request->q . '%');
        }

        return response()->json($companies->get());
    }
}
