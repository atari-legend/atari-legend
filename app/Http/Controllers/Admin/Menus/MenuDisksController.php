<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuDisk;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuDisksController extends Controller
{

    public function create(Request $request)
    {
        $menu = Menu::find($request->menu);

        return view('admin.menus.disks.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $menu->menuSet), $menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $menu), $menu->label),
                    new Crumb('', 'Create disk'),
                ],
                'menu'         => $menu,
            ]);
    }

    public function store(Request $request)
    {
        $menu = Menu::find($request->menu);
        MenuDisk::create([
            'part'       => $request->part,
            'notes'      => $request->notes,
            'scrolltext' => $request->scrolltext,
            'menu_id'    => $menu->id,
        ]);

        return redirect()->route('admin.menus.menus.edit', $menu);
    }

    public function edit(MenuDisk $disk)
    {
        return view('admin.menus.disks.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $disk->menu->menuSet), $disk->menu->menuSet->name),
                    new Crumb(route('admin.menus.menus.edit', $disk->menu), $disk->menu->label),
                    new Crumb(route('admin.menus.disks.edit', $disk), $disk->label)
                ],
                'menu'        => $disk->menu,
                'disk'        => $disk,
            ]);
    }

    public function update(Request $request, MenuDisk $disk)
    {
        $disk->update([
            'part'       => $request->part,
            'notes'      => $request->notes,
            'scrolltext' => $request->scrolltext,
        ]);

        return redirect()->route('admin.menus.menus.edit', $disk->menu);
    }

    public function destroy(Menu $menu)
    {
    }
}
