<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Category;
use App\Models\Changelog;
use App\Models\Link;
use App\Models\LinkSubmission;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->filled('category')
            ? Category::find($request->category)
            : null;

        $categories = Category::select('categories.*')
            ->orderBy('name')
            ->get();

        $links = Link::select('links.*');

        if ($category !== null) {
            $links->join('link_category', 'link_category.link_id', '=', 'links.id')
                ->where('link_category.category_id', $category->getKey());
        }

        $links = $links
            ->orderBy('name')
            ->paginate(5);

        return view('links.index')
            ->with([
                'categories' => $categories,
                'category'   => $category,
                'links'      => $links,
            ]);
    }

    public function postLink(Request $request)
    {
        $submission = new LinkSubmission();
        $submission->name = $request->name;
        $submission->url = $request->url;
        $submission->description = $request->description;
        $submission->date = time();

        $request->user()->linkSubmissions()->save($submission);

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
