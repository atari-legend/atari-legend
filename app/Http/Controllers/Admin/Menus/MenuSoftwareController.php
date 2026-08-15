<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuSoftwareController extends Controller
{
    const VALIDATION_RULES = [
        'name' => 'required',
    ];

    public function index()
    {
        return view('admin.menus.software.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.software.index'), 'Software'),
                ],
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
                'games'       => collect([]),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(MenuSoftwareController::VALIDATION_RULES);

        $software = MenuSoftware::create([
            'name'                          => $request->name,
            'menu_software_content_type_id' => $request->type,
            'demozoo_id'                    => $request->demozoo,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menu Software',
            'section_id'       => $software->getKey(),
            'section_name'     => $software->name,
            'sub_section'      => 'Software',
            'sub_section_id'   => $software->getKey(),
            'sub_section_name' => $software->name,
        ]);

        return redirect()->route('admin.menus.software.index');
    }

    public function edit(MenuSoftware $software)
    {
        $games = collect([]);
        if ('game' === strtolower($software->menuSoftwareContentType->name)) {
            $games = Game::where('game_name', '=', $software->name)->get();
        }

        return view('admin.menus.software.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.software.index'), 'Software'),
                    new Crumb(route('admin.menus.software.edit', $software), $software->name),
                ],
                'software'    => $software,
                'games'       => $games,
                'types'       => MenuSoftwareContentType::all(),
            ]);
    }

    public function update(Request $request, MenuSoftware $software)
    {
        $request->validate(MenuSoftwareController::VALIDATION_RULES);

        $oldName = $software->name;

        $software->update([
            'name'                          => $request->name,
            'menu_software_content_type_id' => $request->type,
            'demozoo_id'                    => $request->demozoo,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Menu Software',
            'section_id'       => $software->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Software',
            'sub_section_id'   => $software->getKey(),
            'sub_section_name' => $software->name,
        ]);

        return redirect()->route('admin.menus.software.index');
    }

    public function destroy(MenuSoftware $software)
    {
        $oldName = $software->name;

        $software->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Menu Software',
            'section_id'       => $software->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Software',
            'sub_section_id'   => $software->getKey(),
            'sub_section_name' => $oldName,
        ]);

        return redirect()->route('admin.menus.software.index');
    }
}
