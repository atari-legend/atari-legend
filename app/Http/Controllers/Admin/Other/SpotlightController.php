<?php

namespace App\Http\Controllers\Admin\Other;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Screenshot;
use App\Models\Spotlight;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpotlightController extends Controller
{
    public function index()
    {
        return view('admin.others.spotlights.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.spotlights.index'), 'Spotlights'),
                ],
            ]);
    }

    public function edit(Spotlight $spotlight)
    {
        return view('admin.others.spotlights.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.spotlights.index'), 'Spotlights'),
                    new Crumb('', "Spotlight {$spotlight->getKey()}"),
                ],
                'spotlight'   => $spotlight,
            ]);
    }

    public function create()
    {
        return view('admin.others.spotlights.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.spotlights.index'), 'Spotlights'),
                    new Crumb('', 'Create spotlight'),
                ],
            ]);
    }

    public function update(Request $request, Spotlight $spotlight)
    {
        $request->validate([
            'spotlight' => 'required',
            'link'      => 'required|url',
        ]);

        $oldText = $spotlight->spotlight;
        $spotlight->update([
            'spotlight' => $request->spotlight,
            'link'      => $request->link,
        ]);

        $this->addOrUpdateImage($request, $spotlight);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Trivia',
            'section_id'       => $spotlight->getKey(),
            'section_name'     => Str::words($oldText, 15),
            'sub_section'      => 'Spotlight',
            'sub_section_id'   => $spotlight->getKey(),
            'sub_section_name' => Str::words($spotlight->spotlight, 15),
        ]);

        return redirect()->route('admin.others.spotlights.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'spotlight' => 'required',
            'link'      => 'required|url',
        ]);

        $spotlight = Spotlight::create([
            'spotlight' => $request->spotlight,
            'link'      => $request->link,
        ]);

        $this->addOrUpdateImage($request, $spotlight);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Trivia',
            'section_id'       => $spotlight->getKey(),
            'section_name'     => Str::words($spotlight->spotlight, 15),
            'sub_section'      => 'Spotlight',
            'sub_section_id'   => $spotlight->getKey(),
            'sub_section_name' => Str::words($spotlight->spotlight, 15),
        ]);

        return redirect()->route('admin.others.spotlights.index');
    }

    public function destroy(Spotlight $spotlight)
    {
        $this->destroyImage($spotlight);
        $spotlight->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Trivia',
            'section_id'       => $spotlight->getKey(),
            'section_name'     => Str::words($spotlight->spotlight, 15),
            'sub_section'      => 'Spotlight',
            'sub_section_id'   => $spotlight->getKey(),
            'sub_section_name' => Str::words($spotlight->spotlight, 15),
        ]);

        return redirect()->route('admin.others.spotlights.index');
    }

    public function destroyImage(Spotlight $spotlight)
    {
        if ($spotlight->screenshot) {
            Storage::disk('public')->delete($spotlight->screenshot->getPath('spotlight'));
            $spotlight->screenshot->delete();

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Trivia',
                'section_id'       => $spotlight->getKey(),
                'section_name'     => Str::words($spotlight->spotlight, 15),
                'sub_section'      => 'Spotlight',
                'sub_section_id'   => $spotlight->getKey(),
                'sub_section_name' => Str::words($spotlight->spotlight, 15),
            ]);
        }

        return redirect()->route('admin.others.spotlights.edit', $spotlight);
    }

    private function addOrUpdateImage(Request $request, Spotlight $spotlight)
    {
        if ($request->hasFile('image')) {
            $screenshot = $spotlight->screenshot;

            if (! $screenshot) {
                $screenshot = new Screenshot();
                $screenshot->save();
                $spotlight->screenshot()->associate($screenshot);
                $spotlight->save();
            }

            $image = $request->file('image');
            $image->storeAs($screenshot->getFolder('spotlight'), $screenshot->getKey().'.'.$image->extension(), 'public');

            $screenshot->update(['imgext' => $image->extension()]);
        }
    }
}
