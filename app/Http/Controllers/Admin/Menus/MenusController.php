<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenusController extends Controller
{

    public function create(Request $request)
    {
        $set = MenuSet::find($request->set);

        return view('admin.menus.menus.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $set), $set->name),
                    new Crumb('', 'Create menu'),
                ],
                'set'         => $set,
            ]);
    }

    public function store(Request $request)
    {
        $set = MenuSet::find($request->set);
        $menu = Menu::create([
            'number'       => $request->number,
            'issue'        => $request->issue,
            'version'      => $request->version,
            'date'         => $request->date,
            'menu_set_id'  => $set->id,
        ]);

        return redirect()->route('admin.menus.menus.edit', $menu);
    }

    public function edit(Menu $menu)
    {
        return view('admin.menus.menus.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $menu->menuSet), $menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $menu), $menu->label),
                ],
                'set'         => $menu->menuSet,
                'menu'        => $menu,
            ]);
    }

    public function update(Request $request, Menu $menu)
    {
        $menu->update([
            'number'       => $request->number,
            'issue'        => $request->issue,
            'version'      => $request->version,
            'date'         => $request->date,
        ]);

        $request->session()->flash('alert-success', 'Saved');
        return redirect()->route('admin.menus.menus.edit', $menu);
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.sets.edit', $menu->menuSet);
    }

}
