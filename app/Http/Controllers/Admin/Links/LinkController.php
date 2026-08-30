<?php

namespace App\Http\Controllers\Admin\Links;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Website;
use App\Models\WebsiteCategory;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LinkController extends Controller
{
    const VALIDATION_RULES = [
        'name'         => 'required|max:128',
        'url'          => 'required|url|max:255',
        'description'  => 'nullable',
        'inactive'     => 'nullable|boolean',
        'categories'   => 'nullable|array',
        'categories.*' => 'exists:website_categories,id',
        'image'        => 'nullable|image',
    ];

    public function index()
    {
        return view('admin.links.links.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                ],
            ]);
    }

    public function create()
    {
        $categories = WebsiteCategory::orderBy('website_category_name')->get();

        return view('admin.links.links.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                    new Crumb('', 'Create link'),
                ],
                'categories' => $categories,
            ]);
    }

    public function edit(Website $link)
    {
        $categories = WebsiteCategory::orderBy('website_category_name')->get();

        return view('admin.links.links.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                    new Crumb('', $link->website_name),
                ],
                'link'       => $link,
                'categories' => $categories,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(self::VALIDATION_RULES);

        $link = Website::create([
            'website_name' => $request->name,
            'website_url'  => $request->url,
            'description'  => $request->description,
            'website_date' => time(),
            'user_id'      => $request->user()->getKey(),
            'inactive'     => $request->boolean('inactive'),
        ]);

        $link->categories()->sync($request->input('categories', []));

        $this->addOrUpdateImage($request, $link);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Links',
            'section_id'       => $link->getKey(),
            'section_name'     => $link->website_name,
            'sub_section'      => 'Link',
            'sub_section_id'   => $link->getKey(),
            'sub_section_name' => $link->website_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.links.links.edit', $link);
        }

        return redirect()->route('admin.links.links.index');
    }

    public function update(Request $request, Website $link)
    {
        $request->validate(self::VALIDATION_RULES);

        $link->update([
            'website_name' => $request->name,
            'website_url'  => $request->url,
            'description'  => $request->description,
            'inactive'     => $request->boolean('inactive'),
        ]);

        $link->categories()->sync($request->input('categories', []));

        $this->addOrUpdateImage($request, $link);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Links',
            'section_id'       => $link->getKey(),
            'section_name'     => $link->website_name,
            'sub_section'      => 'Link',
            'sub_section_id'   => $link->getKey(),
            'sub_section_name' => $link->website_name,
        ]);

        if ($request->stay) {
            return redirect()->route('admin.links.links.edit', $link);
        }

        return redirect()->route('admin.links.links.index');
    }

    public function destroy(Website $link)
    {
        $name = $link->website_name;
        $id = $link->getKey();

        $this->deleteImageFile($link);
        $link->categories()->detach();
        $link->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Links',
            'section_id'       => $id,
            'section_name'     => $name,
            'sub_section'      => 'Link',
            'sub_section_id'   => $id,
            'sub_section_name' => $name,
        ]);

        return redirect()->route('admin.links.links.index');
    }

    public function destroyImage(Website $link)
    {
        if ($link->file) {
            $this->deleteImageFile($link);
            $link->website_imgext = null;
            $link->save();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Links',
                'section_id'       => $link->getKey(),
                'section_name'     => $link->website_name,
                'sub_section'      => 'Image',
                'sub_section_id'   => $link->getKey(),
                'sub_section_name' => $link->website_name,
            ]);
        }

        return redirect()->route('admin.links.links.edit', $link);
    }

    private function addOrUpdateImage(Request $request, Website $link): void
    {
        if ($request->hasFile('image')) {
            $action = $link->file ? Changelog::UPDATE : Changelog::INSERT;
            $this->deleteImageFile($link);

            $image = $request->file('image');
            $image->storeAs('images/website_images', $link->getKey() . '.' . $image->extension(), 'public');

            $link->website_imgext = $image->extension();
            $link->save();

            ChangelogHelper::insert([
                'action'           => $action,
                'section'          => 'Links',
                'section_id'       => $link->getKey(),
                'section_name'     => $link->website_name,
                'sub_section'      => 'Image',
                'sub_section_id'   => $link->getKey(),
                'sub_section_name' => $link->website_name,
            ]);
        }
    }

    private function deleteImageFile(Website $link): void
    {
        if ($link->file) {
            Storage::disk('public')->delete($link->path);
        }
    }
}
