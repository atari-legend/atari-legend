<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuSoftwareController extends Controller
{
    public function index()
    {
        $softwares = MenuSoftware::orderBy('name')
            ->get();

        return view('admin.menus.software.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.software.index'), 'Software'),
                ],
                'softwares'   => $softwares,
            ]);
    }

    public function create()
    {
        return view('admin.menus.software.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.software.index'), 'Software'),
                    new Crumb('', 'Create'),
                ],
                'types'       => MenuSoftwareContentType::all(),
            ]);
    }

    public function store(Request $request)
    {
        MenuSoftware::create([
            'name' => $request->name,
            'menu_software_content_type_id' => $request->type,
            'demozoo_id' => $request->demozoo,
        ]);

        return redirect()->route('admin.menus.software.index');
    }

    public function edit(MenuSoftware $software)
    {
        return view('admin.menus.software.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.software.index'), 'Software'),
                    new Crumb(route('admin.menus.software.edit', $software), $software->name),
                ],
                'software'    => $software,
                'types'       => MenuSoftwareContentType::all(),
            ]);
    }

    public function update(Request $request, MenuSoftware $software)
    {
        $software->update([
            'name' => $request->name,
            'menu_software_content_type_id' => $request->type,
            'demozoo_id' => $request->demozoo,
        ]);

        return redirect()->route('admin.menus.software.index');
    }

    public function destroy(MenuSoftware $software)
    {
        $software->delete();
        return redirect()->route('admin.menus.software.index');
    }
}
