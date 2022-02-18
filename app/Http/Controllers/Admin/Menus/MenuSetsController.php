<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Crew;
use App\Models\MenuSet;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MenuSetsController extends Controller
{
    const VALIDATION_RULES = [
        'name'  => 'required',
        'sort'  => 'required',
        'crews' => 'required',
    ];

    public function index(Request $request)
    {
        $sets = MenuSet::select()
            ->orderBy('name');

        if ($request->letter && $request->letter === '0-9') {
            $sets = $sets
                ->where('name', 'regexp', '^[0-9]+')
                // Essentially disable pagination when filtering on a letter
                ->paginate(PHP_INT_MAX);
        } elseif ($request->letter) {
            $sets = $sets
                ->where('name', 'like', $request->letter . '%')
                // Essentially disable pagination when filtering on a letter
                ->paginate(PHP_INT_MAX);
        } else {
            $sets = $sets->paginate(20);
        }

        return view('admin.menus.sets.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                ],
                'sets'        => $sets,
                'letter'      => $request->letter,
            ]);
    }

    public function create()
    {
        return view('admin.menus.sets.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb('', 'Create'),
                ],
                'crews'       => Crew::orderBy('crew_name')->get(),
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(MenuSetsController::VALIDATION_RULES);

        $set = MenuSet::create([
            'name'       => $request->name,
            'menus_sort' => $request->sort,
        ]);

        collect($request->crews)
            ->map(function ($crewId) {
                return Crew::find($crewId);
            })
            ->each(function ($crew) use ($set) {
                $set->crews()->attach($crew);
            });

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Menus',
            'section_id'       => $set->getKey(),
            'section_name'     => $set->name,
            'sub_section'      => 'Set',
            'sub_section_id'   => $set->getKey(),
            'sub_section_name' => $set->name,
        ]);

        return redirect()->route('admin.menus.sets.edit', $set);
    }

    public function edit(MenuSet $set)
    {
        return view('admin.menus.sets.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb('', 'Menus'),
                    new Crumb(route('admin.menus.sets.index'), 'Sets'),
                    new Crumb(route('admin.menus.sets.edit', $set), $set->name),
                ],
                'set'         => $set,
                'crews'       => Crew::orderBy('crew_name')->get(),
            ]);
    }

    public function update(Request $request, MenuSet $set)
    {
        $request->validate(MenuSetsController::VALIDATION_RULES);

        $set->crews()->detach();

        collect($request->crews)
            ->map(function ($crewId) {
                return Crew::find($crewId);
            })
            ->each(function ($crew) use ($set) {
                $set->crews()->attach($crew);
            });

        $set->update([
            'name'       => $request->name,
            'menus_sort' => $request->sort,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Menus',
            'section_id'       => $set->getKey(),
            'section_name'     => $set->getOriginal('name'),
            'sub_section'      => 'Set',
            'sub_section_id'   => $set->getKey(),
            'sub_section_name' => $set->name,
        ]);

        $request->session()->flash('alert-success', 'Saved');

        return redirect()->route('admin.menus.sets.edit', $set);
    }

    public function destroy(MenuSet $set)
    {
        $set->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Menus',
            'section_id'       => $set->getKey(),
            'section_name'     => $set->name,
            'sub_section'      => 'Set',
            'sub_section_id'   => $set->getKey(),
            'sub_section_name' => $set->name,
        ]);

        return redirect()->route('admin.menus.sets.index');
    }
}
