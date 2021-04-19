<?php

namespace App\Http\Controllers;

use App\Models\MenuDisk;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Support\Facades\DB;

class MenuSetController extends Controller
{
    const PAGE_SIZE = 20;

    const INTACT_CONDITION_ID = 4;

    const CONDITION_CLASSES = [
        1 => 'danger',
        2 => 'warning',
        3 => 'warning',
        4 => 'success',
    ];

    public function index()
    {

        // Get all sets with their total count of disks, and count of
        // missing disks
        $sets = DB::table('menu_sets')
            ->select('name', 'menu_sets.id')
            ->selectRaw("count('menu_disks.id') as disks")
            ->selectRaw('convert(sum(case when menu_disks.menu_disk_condition_id != ? then 1 else 0 end), unsigned integer) as missing', [
                MenuSetController::INTACT_CONDITION_ID,
            ])
            ->join('menus', 'menus.menu_set_id', 'menu_sets.id')
            ->join('menu_disks', 'menu_disks.menu_id', 'menus.id')
            ->groupBy('menu_sets.id')
            ->get();

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

        $missingDiskCount = DB::table('menu_disks')
            ->join('menus', 'menu_id', '=', 'menus.id')
            ->where('menus.menu_set_id', '=', $set->id)
            ->where('menu_disk_condition_id', '!=', MenuSetController::INTACT_CONDITION_ID)
            ->count();

        return view('menus.show')->with([
            'menuset'          => $set,
            'disks'            => $disks,
            'missingCount'     => $missingDiskCount,
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
            'software'         => $software,
            'menuDisks'        => $menuDisks,
            'conditionClasses' => MenuSetController::CONDITION_CLASSES,
        ]);
    }
}
