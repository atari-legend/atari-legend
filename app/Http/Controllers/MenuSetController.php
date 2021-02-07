<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuDiskContent;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MenuSetController extends Controller
{

    const PAGE_SIZE = 10;

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
        $disks = MenuDisk::select('menu_disks.*')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->orderBy('number', $set->menus_sort)
            ->orderBy('issue', $set->menus_sort)
            ->orderBy('version')
            ->orderBy('part')
            ->paginate(MenuSetController::PAGE_SIZE);

        return view('menus.show')->with([
            'menuset'          => $set,
            'disks'            => $disks,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }

    public function software(MenuSoftware $software)
    {
        $menuDisks = $software->menuDiskContents
            ->map(function ($menuDiskContent) {
                return $menuDiskContent->menuDisk;
            })
            ->unique('id');

        return view('menus.software')->with([
            'software'  => $software,
            'menuDisks' => $menuDisks,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }
}
