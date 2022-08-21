<?php

namespace App\Http\Controllers\Admin\Magazines;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Location;
use App\Models\Magazine;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MagazinesController extends Controller
{
    public function index()
    {
        return view('admin.magazines.magazines.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.magazines.index'), 'Magazines'),
                ],
            ]);
    }

    public function edit(Magazine $magazine)
    {
        return view('admin.magazines.magazines.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.magazines.index'), 'Magazines'),
                    new Crumb('', $magazine->name),
                ],
                'magazine'   => $magazine,
                'locations'  => Location::orderBy('continent_code', 'asc')
                    ->orderBy('type', 'asc')
                    ->orderBy('name', 'asc')
                    ->get(),
            ]);
    }

    public function create()
    {
        return view('admin.magazines.magazines.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.magazines.index'), 'Magazines'),
                    new Crumb('', 'Create magazine'),
                ],
                'locations'  => Location::orderBy('continent_code', 'asc')
                    ->orderBy('type', 'asc')
                    ->orderBy('name', 'asc')
                    ->get(),
            ]);
    }

    public function update(Request $request, Magazine $magazine)
    {
        $request->validate([
            'name'     => 'required',
            'location' => 'numeric|nullable',
        ]);

        $oldName = $magazine->name;
        $magazine->name = $request->name;
        if ($request->location) {
            $magazine->location()->associate(Location::find($request->location));
        } else {
            $magazine->location()->dissociate();
        }
        $magazine->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $magazine->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Magazine',
            'sub_section_id'   => $magazine->getKey(),
            'sub_section_name' => $magazine->name,
        ]);

        return redirect()->route('admin.magazines.magazines.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'location' => 'numeric|nullable',
        ]);

        $magazine = new Magazine([
            'name' => $request->name,
        ]);
        if ($request->location) {
            $magazine->location()->associate(Location::find($request->location));
        }
        $magazine->save();

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Magazines',
            'section_id'       => $magazine->getKey(),
            'section_name'     => $magazine->name,
            'sub_section'      => 'Magazine',
            'sub_section_id'   => $magazine->getKey(),
            'sub_section_name' => $magazine->name,
        ]);

        return redirect()->route('admin.magazines.magazines.edit', $magazine);
    }

    public function destroy(Magazine $magazine)
    {
        $magazine->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Magazines',
            'section_id'       => $magazine->getKey(),
            'section_name'     => $magazine->name,
            'sub_section'      => 'Magazine',
            'sub_section_id'   => $magazine->getKey(),
            'sub_section_name' => $magazine->name,
        ]);

        return redirect()->route('admin.magazines.magazines.index');
    }
}
