<?php

namespace App\Http\Controllers\Admin\Links;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\WebsiteCategory;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class LinkCategoryController extends Controller
{
    const VALIDATION_RULES = [
        'name' => 'required|max:128|unique:website_categories,name',
    ];

    public function index()
    {
        return view('admin.links.categories.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                    new Crumb(route('admin.links.categories.index'), 'Categories'),
                ],
            ]);
    }

    public function create()
    {
        return view('admin.links.categories.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                    new Crumb(route('admin.links.categories.index'), 'Categories'),
                    new Crumb('', 'Create category'),
                ],
            ]);
    }

    public function edit(WebsiteCategory $category)
    {
        return view('admin.links.categories.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.links.links.index'), 'Links'),
                    new Crumb(route('admin.links.categories.index'), 'Categories'),
                    new Crumb('', $category->name),
                ],
                'category' => $category,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(self::VALIDATION_RULES);

        $category = WebsiteCategory::create([
            'name' => $request->name,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Links',
            'section_id'       => $category->getKey(),
            'section_name'     => $category->name,
            'sub_section'      => 'Category',
            'sub_section_id'   => $category->getKey(),
            'sub_section_name' => $category->name,
        ]);

        return redirect()->route('admin.links.categories.index');
    }

    public function update(Request $request, WebsiteCategory $category)
    {
        $request->validate([
            'name' => 'required|max:128|unique:website_categories,name,' . $category->getKey() . ',id',
        ]);

        $oldName = $category->name;
        $category->update([
            'name' => $request->name,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Links',
            'section_id'       => $category->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Category',
            'sub_section_id'   => $category->getKey(),
            'sub_section_name' => $category->name,
        ]);

        return redirect()->route('admin.links.categories.index');
    }

    public function destroy(WebsiteCategory $category)
    {
        $name = $category->name;
        $id = $category->getKey();

        $category->websites()->detach();
        $category->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Links',
            'section_id'       => $id,
            'section_name'     => $name,
            'sub_section'      => 'Category',
            'sub_section_id'   => $id,
            'sub_section_name' => $name,
        ]);

        return redirect()->route('admin.links.categories.index');
    }
}
