<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use Illuminate\Http\Request;

class IndividualController extends Controller
{
    const MAX = 10;

    public function individuals(Request $request)
    {
        $individuals = Individual::select('ind_name', 'ind_id')
            ->orderBy('ind_name')
            ->limit(10);

        if ($request->filled('q')) {
            $individuals = $individuals->where('ind_name', 'like', '%' . $request->q . '%');
        }

        $results = $individuals->get()
            ->map(function ($individual) {
                $ind_name = $individual->ind_name;
                if ($individual->nicknames->isNotEmpty()) {
                    $ind_name .= ' (aka: '.$individual->nick_list->join(', ').')';
                }
                return [
                    'ind_name' => $ind_name,
                    'ind_id' => $individual->ind_id,
                ];
            });
        return response()->json($results);
    }
}
