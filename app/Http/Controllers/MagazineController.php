<?php

namespace App\Http\Controllers;

use App\Models\Magazine;
use App\Models\MagazineIssue;

class MagazineController extends Controller
{
    const PAGE_SIZE = 20;

    public function index()
    {
        return view('magazines.index')
            ->with([
                'magazines' => Magazine::orderBy('name')->get(),
            ]);
    }

    public function show(Magazine $magazine)
    {
        $issues = MagazineIssue::where('magazine_id', '=', $magazine->id)
            ->orderBy('issue')
            ->paginate(MagazineController::PAGE_SIZE);

        return view('magazines.show')
            ->with([
                'magazine' => $magazine,
                'issues'   => $issues,
            ]);
    }
}
