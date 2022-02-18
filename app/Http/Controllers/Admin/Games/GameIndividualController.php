<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Individual;
use App\Models\IndividualText;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GameIndividualController extends Controller
{
    public function index()
    {
        return view('admin.games.individuals.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.individuals.index'), 'Individuals'),
                ],
            ]);
    }

    public function create()
    {
        return view('admin.games.individuals.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.individuals.index'), 'Individuals'),
                    new Crumb(route('admin.games.individuals.create'), 'Create'),
                ],
            ]);
    }

    public function edit(Individual $individual)
    {
        // Ensure we always edit the "main" individual and not one
        // of the nicknames
        $ind = $individual;
        if ($ind->individuals->isNotEmpty()) {
            $ind = $ind->individuals->first();
        }

        return view('admin.games.individuals.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.individuals.index'), 'Individuals'),
                    new Crumb(route('admin.games.individuals.edit', $ind), $ind->ind_name),
                ],
                'individual'  => $ind,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => ['required', Rule::unique('individuals', 'ind_name')],
            'email' => 'nullable|email',
        ]);

        $individual = new Individual(['ind_name' => $request->name]);
        $individual->save();

        $ext = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/individual_screenshots/', $individual->ind_id . '.' . $avatar->extension(), 'public');
            $ext = $avatar->extension();

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Individuals',
                'section_id'       => $individual->getKey(),
                'section_name'     => $individual->ind_name,
                'sub_section'      => 'Image',
                'sub_section_id'   => $individual->getKey(),
                'sub_section_name' => $individual->ind_name,
            ]);
        }

        $text = new IndividualText([
            'ind_profile' => $request->profile,
            'ind_email'   => $request->email,
            'ind_imgext'  => $ext,
        ]);
        $individual->text()->save($text);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Individuals',
            'section_id'       => $individual->getKey(),
            'section_name'     => $individual->ind_name,
            'sub_section'      => 'Individual',
            'sub_section_id'   => $individual->getKey(),
            'sub_section_name' => $individual->ind_name,
        ]);

        return redirect()->route('admin.games.individuals.edit', $individual);
    }

    public function update(Request $request, Individual $individual)
    {
        $request->validate([
            'name'  => ['required', Rule::unique('individuals', 'ind_name')->ignore($individual->ind_id, 'ind_id')],
            'email' => 'nullable|email',
        ]);

        $ext = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/individual_screenshots/', $individual->ind_id . '.' . $avatar->extension(), 'public');
            $ext = $avatar->extension();

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Individuals',
                'section_id'       => $individual->getKey(),
                'section_name'     => $individual->ind_name,
                'sub_section'      => 'Image',
                'sub_section_id'   => $individual->getKey(),
                'sub_section_name' => $individual->ind_name,
            ]);
        }

        $individual->update([
            'ind_name'        => $request->name,
        ]);

        $attrs = [
            'ind_profile' => $request->profile,
            'ind_email'   => $request->email,
            'ind_imgext'  => $ext,
        ];

        if ($individual->text) {
            $individual->text->update($attrs);
        } else {
            $text = new IndividualText($attrs);
            $individual->text()->save($text);
        }

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Individuals',
            'section_id'       => $individual->getKey(),
            'section_name'     => $individual->ind_name,
            'sub_section'      => 'Individual',
            'sub_section_id'   => $individual->getKey(),
            'sub_section_name' => $individual->ind_name,
        ]);

        return redirect()->route('admin.games.individuals.index');
    }

    public function destroy(Individual $individual)
    {
        $this->destroyAvatar($individual);
        $individual->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Individuals',
            'section_id'       => $individual->getKey(),
            'section_name'     => $individual->ind_name,
            'sub_section'      => 'Individual',
            'sub_section_id'   => $individual->getKey(),
            'sub_section_name' => $individual->ind_name,
        ]);

        return redirect()->route('admin.games.individuals.index');
    }

    public function destroyAvatar(Individual $individual)
    {
        if ($individual->avatar) {
            Storage::disk('public')->delete('images/individual_screenshots/' . $individual->ind_id . '.' . $individual->text->ind_imgext);
            $individual->text->ind_imgext = null;
            $individual->text->save();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Individuals',
                'section_id'       => $individual->getKey(),
                'section_name'     => $individual->ind_name,
                'sub_section'      => 'Image',
                'sub_section_id'   => $individual->getKey(),
                'sub_section_name' => $individual->ind_name,
            ]);
        }

        return redirect()->route('admin.games.individuals.edit', $individual);
    }

    public function storeNickname(Request $request, Individual $individual)
    {
        $request->validate(['nickname' => ['required', Rule::unique('individuals', 'ind_name')]]);

        $nickname = new Individual(['ind_name' => $request->nickname]);
        $individual->nicknames()->save($nickname);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Individuals',
            'section_id'       => $individual->getKey(),
            'section_name'     => $individual->ind_name,
            'sub_section'      => 'Nickname',
            'sub_section_id'   => $nickname->getKey(),
            'sub_section_name' => $nickname->ind_name,
        ]);

        return redirect()->route('admin.games.individuals.edit', $individual);
    }

    public function destroyNickname(Individual $individual, Individual $nickname)
    {
        $nickname->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Individuals',
            'section_id'       => $individual->getKey(),
            'section_name'     => $individual->ind_name,
            'sub_section'      => 'Nickname',
            'sub_section_id'   => $nickname->getKey(),
            'sub_section_name' => $nickname->ind_name,
        ]);

        return redirect()->route('admin.games.individuals.edit', $individual);
    }
}
