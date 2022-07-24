<?php

namespace App\Http\Controllers\Admin\Magazines;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\MagazineIndexType;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MagazineIndexTypesController extends Controller
{
    public function index()
    {
        return view('admin.magazines.index-types.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.index-types.index'), 'Index types'),
                ],
                'types'       => MagazineIndexType::all(),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $type = MagazineIndexType::create(['name' => $request->name]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Magazines',
            'section_id'       => $type->getKey(),
            'section_name'     => $type->name,
            'sub_section'      => 'Index type',
            'sub_section_id'   => $type->getKey(),
            'sub_section_name' => $type->name,
        ]);

        return redirect()->route('admin.magazines.index-types.index');
    }

    public function update(Request $request, MagazineIndexType $indexType)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $oldName = $indexType->name;
        $indexType->update(['name' => $request->name]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $indexType->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Index type',
            'sub_section_id'   => $indexType->getKey(),
            'sub_section_name' => $indexType->name,
        ]);

        return redirect()->route('admin.magazines.index-types.index');
    }

    public function destroy(MagazineIndexType $indexType)
    {
        $indexType->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $indexType->getKey(),
            'section_name'     => $indexType->name,
            'sub_section'      => 'Index type',
            'sub_section_id'   => $indexType->getKey(),
            'sub_section_name' => $indexType->name,
        ]);

        return redirect()->route('admin.magazines.index-types.index');
    }
}
