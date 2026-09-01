<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\Individual;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuCrewController extends Controller
{
    const VALIDATION_RULES = [
        'name'  => 'required',
    ];

    public function index()
    {
        return view('admin.menus.crews.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.crews.index'), 'Crews'),
                ],
            ]);
    }

    public function edit(Crew $crew)
    {
        return view('admin.menus.crews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.crews.index'), 'Crews'),
                    new Crumb(route('admin.menus.crews.edit', $crew), $crew->name),
                ],
                'crew' => $crew,
            ]);
    }

    public function create()
    {
        return view('admin.menus.crews.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.crews.index'), 'Crews'),
                    new Crumb('', 'Create'),
                ],
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(MenuCrewController::VALIDATION_RULES);

        $crew = Crew::create([
            'name'    => $request->name,
            'history' => $request->history,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Crew',
            'sub_section_id'   => $crew->getKey(),
            'sub_section_name' => $crew->name,
        ]);

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function update(Request $request, Crew $crew)
    {
        $request->validate(MenuCrewController::VALIDATION_RULES);

        $oldName = $crew->name;

        $crew->update([
            'name'    => $request->name,
            'history' => $request->history,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Crew',
            'sub_section_id'   => $crew->getKey(),
            'sub_section_name' => $crew->name,
        ]);

        $request->session()->flash('alert-success', 'Saved');

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function destroy(Crew $crew)
    {
        $crew->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Crew',
            'sub_section_id'   => $crew->getKey(),
            'sub_section_name' => $crew->name,
        ]);

        return redirect()->route('admin.menus.crews.index');
    }

    public function addIndividual(Request $request, Crew $crew)
    {
        $individual = Individual::find($request->individual);
        if ($individual) {
            $crew->individuals()->attach($individual);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Crew',
                'section_id'       => $crew->getKey(),
                'section_name'     => $crew->name,
                'sub_section'      => 'Member',
                'sub_section_id'   => $individual->getKey(),
                'sub_section_name' => $individual->name,
            ]);
        }

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function removeIndividual(Crew $crew, Individual $individual)
    {
        $crew->individuals()->detach($individual);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Member',
            'sub_section_id'   => $individual->getKey(),
            'sub_section_name' => $individual->name,
        ]);

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function addSubCrew(Request $request, Crew $crew)
    {
        $subCrew = Crew::find($request->subcrew);
        if ($subCrew) {
            $crew->subCrews()->attach($subCrew);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Crew',
                'section_id'       => $crew->getKey(),
                'section_name'     => $crew->name,
                'sub_section'      => 'Sub Crew',
                'sub_section_id'   => $subCrew->getKey(),
                'sub_section_name' => $subCrew->name,
            ]);
        }

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function removeSubCrew(Crew $crew, Crew $subCrew)
    {
        $crew->subCrews()->detach($subCrew);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Sub Crew',
            'sub_section_id'   => $subCrew->getKey(),
            'sub_section_name' => $subCrew->name,
        ]);

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function removeParentCrew(Crew $crew, Crew $parentCrew)
    {
        $crew->parentCrews()->detach($parentCrew);

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Parent Crew',
            'sub_section_id'   => $parentCrew->getKey(),
            'sub_section_name' => $parentCrew->name,
        ]);

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function storeLogo(Request $request, Crew $crew)
    {
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');

            $crew->logo = strtolower($logoFile->extension());
            $crew->save();

            $logoFile->storeAs('images/crew_logos/', $crew->logo_file, 'public');

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Crew',
                'section_id'       => $crew->getKey(),
                'section_name'     => $crew->name,
                'sub_section'      => 'Logo',
                'sub_section_id'   => $crew->getKey(),
                'sub_section_name' => $crew->logo,
            ]);
        }

        return redirect()->route('admin.menus.crews.edit', $crew);
    }

    public function destroyLogo(Crew $crew)
    {
        Storage::disk('public')->delete('images/crew_logos/' . $crew->logo_file);

        $crew->logo = null;
        $crew->save();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Crew',
            'section_id'       => $crew->getKey(),
            'section_name'     => $crew->name,
            'sub_section'      => 'Logo',
            'sub_section_id'   => $crew->getKey(),
            'sub_section_name' => $crew->name,
        ]);

        return redirect()->route('admin.menus.crews.edit', $crew);
    }
}
