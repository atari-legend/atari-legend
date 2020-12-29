<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\Crew;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuSetsController extends Controller
{
    public function index()
    {
        $sets = MenuSet::orderBy('name')
            ->get();

        return view('admin.menus.sets.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                ],
                'sets'        => $sets,
            ]);
    }

    public function create()
    {
        return view('admin.menus.sets.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb('', 'Create'),
                ],
                'crews'       => Crew::orderBy('crew_name')->get(),
            ]);
    }

    public function store(Request $request)
    {
        $set = MenuSet::create([
            'name'       => $request->name,
            'menus_sort' => $request->sort,
        ]);
        return $this->update($request, $set);
    }

    public function edit(MenuSet $set)
    {
        return view('admin.menus.sets.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $set), $set->name),
                ],
                'set'         => $set,
                'crews'       => Crew::orderBy('crew_name')->get(),
            ]);
    }

    public function update(Request $request, MenuSet $set)
    {
        $set->crews()->detach();

        collect($request->crews)
            ->map(function ($crewId) {
                return Crew::find($crewId);
            })
            ->each(function ($crew) use ($set) {
                $set->crews()->attach($crew);
            });

        $set->update([
            'name'       => $request->name,
            'menus_sort' => $request->sort,
        ]);

        return redirect()->route('admin.menus.sets.index');
    }

    public function destroy(MenuSet $set)
    {
        $set->delete();
        return redirect()->route('admin.menus.sets.index');
    }
}
