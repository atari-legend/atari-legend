<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\MenuDisk;
use App\Models\MenuSet;
use App\Models\MenuSoftware;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuSetController extends Controller
{
    const PAGE_SIZE = 20;

    const SOFTWARE_PAGE_SIZE = 48;

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
            ->orderBy('name')
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

    public function search(Request $request)
    {
        $software = MenuSoftware::select();
        $games = Game::select();

        // Boolean to check if a search can be made
        // Search only works via title or titleAZ. If neither
        // are used then there should be no search results in
        // software or games
        $searchPossible = false;

        if ($request->filled('titleAZ')) {
            if ($request->input('titleAZ') === '0-9') {
                $games->where('game_name', 'regexp', '^[0-9]+');
                $software->where('name', 'regexp', '^[0-9]+');
            } else {
                $games->where('game_name', 'like', $request->input('titleAZ').'%');
                $software->where('name', 'like', $request->input('titleAZ').'%');
            }
            $searchPossible = true;
        }

        if ($request->title) {
            $software->where('name', 'like', '%'.$request->title.'%');
            $searchPossible = true;

            $games->where(function (Builder $query) use ($request) {
                $query->where('game_name', 'like', '%'.$request->input('title').'%')
                    ->orWhereHas('akas', function (Builder $subQuery) use ($request) {
                        $subQuery->where('aka_name', 'like', '%'.$request->input('title').'%');
                    });
            });
        }

        if (!$searchPossible) {
            // Force no software results when there were no titles selected
            $software->where('id', '<', 0);
            $games->where('game_id', '<', 0);
        }

        $games->orderBy('game_name')
            ->paginate(GameSearchController::PAGE_SIZE);

        $software->orderBy('name')
            ->paginate(MenuSetController::PAGE_SIZE);

        return view('menus.search')->with([
            'software' => $software->paginate(48),
            'games'    => $games->paginate(48),
            'title'    => $request->title,
            'titleAZ'  => $request->titleAZ,
        ]);
    }
}
