<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use App\Models\MenuDiskCondition;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuConditionsController extends Controller
{
    public function index()
    {
        $conditions = MenuDiskCondition::orderBy('name')
            ->get();

        return view('admin.menus.conditions.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.conditions.index'), 'Conditions'),
                ],
                'conditions'  => $conditions,
            ]);
    }

    public function create()
    {
        return view('admin.menus.conditions.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.conditions.index'), 'Conditions'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        MenuDiskCondition::create([
            'name' => $request->name,
        ]);

        return redirect()->route('admin.menus.conditions.index');
    }

    public function edit(MenuDiskCondition $condition)
    {
        return view('admin.menus.conditions.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.conditions.index'), 'Conditions'),
                    new Crumb(route('admin.menus.conditions.edit', $condition), $condition->name),
                ],
                'condition'   => $condition,
            ]);
    }

    public function update(Request $request, MenuDiskCondition $condition)
    {
        $condition->update(['name' => $request->name]);

        return redirect()->route('admin.menus.conditions.index');
    }

    public function destroy(MenuDiskCondition $condition)
    {
        $condition->delete();
        return redirect()->route('admin.menus.conditions.index');
    }
}
