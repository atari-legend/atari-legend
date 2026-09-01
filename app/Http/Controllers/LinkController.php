<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\Website;
use App\Models\WebsiteCategory;
use App\Models\WebsiteValidate;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->filled('category')
            ? WebsiteCategory::find($request->category)
            : null;

        $categories = WebsiteCategory::select('website_categories.*')
            ->orderBy('name')
            ->get();

        $websites = Website::select('websites.*');

        if ($category !== null) {
            $websites->join('website_category_cross', 'website_category_cross.website_id', '=', 'websites.id')
                ->where('website_category_cross.website_category_id', $category->getKey());
        }

        $websites = $websites
            ->orderBy('name')
            ->paginate(5);

        return view('links.index')
            ->with([
                'categories'    => $categories,
                'category'      => $category,
                'websites'      => $websites,
            ]);
    }

    public function postLink(Request $request)
    {
        $submission = new WebsiteValidate();
        $submission->name = $request->name;
        $submission->url = $request->url;
        $submission->description = $request->description;
        $submission->date = time();

        $request->user()->websiteSubmissions()->save($submission);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Links',
            'section_id'       => $submission->getKey(),
            'section_name'     => $submission->name,
            'sub_section'      => 'Link submit',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->name,
        ]);

        $request->session()->flash('alert-title', 'Link submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return back();
    }
}
