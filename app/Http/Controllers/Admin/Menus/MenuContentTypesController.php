<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\MenuDiskContentType;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuContentTypesController extends Controller
{
    public function index()
    {
        $contentTypes = MenuDiskContentType::orderBy('name')
            ->get();

        return view('admin.menus.content-types.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('#', 'Menus'),
                    new Crumb(route('admin.menus.content-types.index'), 'Content Types'),
                ],
                'contentTypes'  => $contentTypes,
            ]);
    }

    public function create()
    {
        return view('admin.menus.content-types.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('#', 'Menus'),
                    new Crumb(route('admin.menus.content-types.index'), 'Content Types'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        MenuDiskContentType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.menus.content-types.index');
    }

    public function edit(MenuDiskContentType $contentType)
    {
        return view('admin.menus.content-types.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('#', 'Menus'),
                    new Crumb(route('admin.menus.content-types.index'), 'Content Types'),
                    new Crumb(route('admin.menus.content-types.edit', $contentType), $contentType->name),
                ],
                'contentType' => $contentType,
            ]);
    }

    public function update(Request $request, MenuDiskContentType $contentType)
    {
        $contentType->update(['name' => $request->name]);

        return redirect()->route('admin.menus.content-types.index');
    }

    public function destroy(MenuDiskContentType $contentType)
    {
        $contentType->delete();
        return redirect()->route('admin.menus.content-types.index');
    }
}
