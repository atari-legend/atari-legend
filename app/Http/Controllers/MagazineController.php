<?php

namespace App\Http\Controllers;

use App\Models\Magazine;

class MagazineController extends Controller
{
    public function index()
    {
        return view('magazines.index')
            ->with([
                'magazines' => Magazine::orderBy('name')->get(),
            ]);
    }

    public function show(Magazine $magazine)
    {
        return view('magazines.show')
            ->with([
                'magazine' => $magazine,
            ]);
    }
}
