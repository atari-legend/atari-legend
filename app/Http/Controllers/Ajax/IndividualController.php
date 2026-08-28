<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Individual;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IndividualController extends Controller
{
    public function individuals(Request $request)
    {
        $individuals = Individual::select('ind_name', 'id')
            ->orderBy('ind_name')
            ->limit(10);

        if ($request->filled('q')) {
            $individuals = $individuals->where('ind_name', 'like', '%' . $request->q . '%');
        }

        $results = $individuals->get()
            ->map(function ($individual) {
                $ind_name = $individual->ind_name;
                if ($individual->aka_list->isNotEmpty()) {
                    $ind_name .= ' (aka: ' . $individual->aka_list->join(', ') . ')';
                }

                if ($individual->games->isNotEmpty()) {
                    $ind_name .= ' [' . Str::limit($individual->games->pluck('game_name')->unique()->join(', '), 45, '…') . ']';
                }

                return [
                    // A wire name, not a column name: id is the key every
                    // autocomplete endpoint emits, and the seven Blade
                    // data-autocomplete-id attributes that read this payload
                    // name it too. Renaming it here without moving all of
                    // them assigns undefined into the hidden field, which
                    // submits happily with nothing in the log. Those seven
                    // attributes and this endpoint make eight: all or none.
                    'ind_name' => $ind_name,
                    'id'       => $individual->getKey(),
                ];
            });

        return response()->json($results);
    }
}
