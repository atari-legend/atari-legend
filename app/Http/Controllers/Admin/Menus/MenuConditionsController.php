<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
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
        $condition = MenuDiskCondition::create([
            'name' => $request->name,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menu Conditions',
            'section_id'       => $condition->getKey(),
            'section_name'     => $condition->name,
            'sub_section'      => 'Condition',
            'sub_section_id'   => $condition->getKey(),
            'sub_section_name' => $condition->name,
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
        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Menu Conditions',
            'section_id'       => $condition->getKey(),
            'section_name'     => $condition->name,
            'sub_section'      => 'Condition',
            'sub_section_id'   => $condition->getKey(),
            'sub_section_name' => $request->name,
        ]);

        $condition->update(['name' => $request->name]);

        return redirect()->route('admin.menus.conditions.index');
    }

    public function destroy(MenuDiskCondition $condition)
    {
        $condition->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Menu Conditions',
            'section_id'       => $condition->getKey(),
            'section_name'     => $condition->name,
            'sub_section'      => 'Condition',
            'sub_section_id'   => $condition->getKey(),
            'sub_section_name' => $condition->name,
        ]);

        return redirect()->route('admin.menus.conditions.index');
    }
}
