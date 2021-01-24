<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\MenuSoftwareContentType;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuSoftwareContentTypesController extends Controller
{
    public function index()
    {
        $contentTypes = MenuSoftwareContentType::orderBy('name')
            ->get();

        return view('admin.menus.content-types.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
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
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.content-types.index'), 'Content Types'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        MenuSoftwareContentType::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.menus.content-types.index');
    }

    public function edit(MenuSoftwareContentType $contentType)
    {
        return view('admin.menus.content-types.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.content-types.index'), 'Content Types'),
                    new Crumb(route('admin.menus.content-types.edit', $contentType), $contentType->name),
                ],
                'contentType' => $contentType,
            ]);
    }

    public function update(Request $request, MenuSoftwareContentType $contentType)
    {
        $contentType->update(['name' => $request->name]);

        return redirect()->route('admin.menus.content-types.index');
    }

    public function destroy(MenuSoftwareContentType $contentType)
    {
        $contentType->delete();
        return redirect()->route('admin.menus.content-types.index');
    }
}
