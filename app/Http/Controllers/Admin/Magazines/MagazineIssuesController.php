<?php

namespace App\Http\Controllers\Admin\Magazines;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class MagazineIssuesController extends Controller
{
    const VALIDATION_RULES = [
        'issue'          => 'numeric',
        'archiveorg_url' => [
            'nullable',
            'regex:@https://archive.org/details/[^/]+/@',
        ],
        'published' => 'nullable|date',
        'barcode'   => 'nullable|numeric',
    ];

    public function edit(Magazine $magazine, MagazineIssue $issue)
    {
        return view('admin.magazines.issues.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.magazines.index'), 'Magazines'),
                    new Crumb(route('admin.magazines.magazines.edit', $issue->magazine), $issue->magazine->name),
                    new Crumb('', $issue->issue),
                ],
                'magazine' => $issue->magazine,
                'issue'    => $issue,
            ]);
    }

    public function create(Magazine $magazine)
    {
        return view('admin.magazines.issues.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.magazines.magazines.index'), 'Magazines'),
                    new Crumb(route('admin.magazines.magazines.edit', $magazine), $magazine->name),
                    new Crumb('', 'Create issue'),
                ],
                'magazine' => $magazine,
            ]);
    }

    public function update(Request $request, Magazine $magazine, MagazineIssue $issue)
    {
        $request->validate(MagazineIssuesController::VALIDATION_RULES);

        $issue->update([
            'issue'          => $request->issue,
            'archiveorg_url' => $request->archiveorg_url,
            'published'      => $request->published,
            'barcode'        => $request->barcode,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $issue->magazine->getKey(),
            'section_name'     => $issue->magazine->name,
            'sub_section'      => 'Issue',
            'sub_section_id'   => $issue->getKey(),
            'sub_section_name' => $issue->issue,
        ]);

        return redirect()->route('admin.magazines.magazines.edit', $issue->magazine);
    }

    public function store(Request $request, Magazine $magazine)
    {
        $request->validate(MagazineIssuesController::VALIDATION_RULES);

        $issue = new MagazineIssue([
            'issue'          => $request->issue,
            'archiveorg_url' => $request->archiveorg_url,
            'published'      => $request->published,
            'barcode'        => $request->barcode,
        ]);
        $magazine->issues()->save($issue);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Magazines',
            'section_id'       => $magazine->getKey(),
            'section_name'     => $magazine->name,
            'sub_section'      => 'Issue',
            'sub_section_id'   => $issue->getKey(),
            'sub_section_name' => $issue->issue,
        ]);

        return redirect()->route('admin.magazines.magazines.edit', $issue->magazine);
    }
}
