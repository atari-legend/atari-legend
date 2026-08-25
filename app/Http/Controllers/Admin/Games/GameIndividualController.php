<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Individual;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        // Find possible duplicates for the main name
        $duplicates = Individual::where('ind_name', '=', $ind->ind_name)
            ->whereKeyNot($ind->getKey())
            ->get();

        // Find possible duplicates for each nick
        $nickDuplicates = $ind->nicknames()
            ->get()
            ->mapWithKeys(function ($item) use ($ind) {
                $i = Individual::where('ind_name', '=', $item->ind_name)
                    ->get()
                    ->filter(function ($item2) use ($ind) {
                        return ! $ind->nicknames->contains($item2);
                    });

                return [$item->getKey() => $i];
            });

        return view('admin.games.individuals.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.individuals.index'), 'Individuals'),
                    new Crumb(route('admin.games.individuals.edit', $ind), $ind->ind_name),
                ],
                'individual'      => $ind,
                'duplicates'      => $duplicates,
                'nickDuplicates'  => $nickDuplicates,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => ['required'],
            'email' => 'nullable|email',
        ]);

        $individual = new Individual(['ind_name' => $request->name]);
        $individual->save();

        $ext = null;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/individual_screenshots/', $individual->getKey() . '.' . $avatar->extension(), 'public');
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

        // A blank field is stored as NULL rather than an empty string, so that
        // "has a bio" stays a question the database can answer
        $individual->update([
            'ind_profile' => $request->filled('profile') ? $request->profile : null,
            'ind_email'   => $request->filled('email') ? $request->email : null,
            'ind_imgext'  => $ext,
        ]);

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
            'name'  => ['required'],
            'email' => 'nullable|email',
        ]);

        // Keep the avatar already on file when the form comes back without one,
        // which is every save that is not about the avatar. Writing null here
        // dropped the avatar on any later edit, and destroyAvatar() builds its
        // path out of the same column - so the file was then stranded on disk
        // with nothing in the admin able to reach it.
        $ext = $individual->ind_imgext;
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatar->storeAs('images/individual_screenshots/', $individual->getKey() . '.' . $avatar->extension(), 'public');
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
            'ind_name'    => $request->name,
            'ind_profile' => $request->filled('profile') ? $request->profile : null,
            'ind_email'   => $request->filled('email') ? $request->email : null,
            'ind_imgext'  => $ext,
        ]);

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
            Storage::disk('public')->delete($individual->path);
            $individual->ind_imgext = null;
            $individual->save();

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
