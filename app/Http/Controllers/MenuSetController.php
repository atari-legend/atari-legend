<?php

namespace App\Http\Controllers;

use App\Models\MenuSet;
use Illuminate\Http\Request;

class MenuSetController extends Controller
{
    const CONDITION_CLASSES = [
        1 => 'danger',
        2 => 'warning',
        3 => 'warning',
        4 => 'success',
    ];

    public function index()
    {
        $sets = MenuSet::orderBy('name')->get();

        return view('menus.index')->with([
            'menusets' => $sets,
        ]);
    }

    public function show(MenuSet $set)
    {
        return view('menus.show')->with([
            'menuset' => $set,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }
}
