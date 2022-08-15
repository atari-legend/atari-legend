<?php

namespace App\Http\Controllers\Admin\Magazines;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MagazineIssuesController extends Controller
{
    const VALIDATION_RULES = [
        'issue'          => 'numeric',
        'archiveorg_url' => [
            'nullable',
            'regex:@https://archive.org/details/[^/]+/@',
        ],
        'published' => 'nullable|date',
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
        ]);

        $this->addOrUpdateImage($request, $issue);

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
        ]);
        $magazine->issues()->save($issue);

        $this->addOrUpdateImage($request, $issue);

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

    private function addOrUpdateImage(Request $request, MagazineIssue $issue)
    {
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $issue->update(['imgext' => $image->extension()]);
            $image->storeAs($issue->image_path, $issue->image_file, 'public');
        }
    }

    public function fetchImage(Request $request, Magazine $magazine, MagazineIssue $issue)
    {
        $id = $request->id;
        $url = "https://archive.org/download/{$id}/page/cover_w600.jpg";

        $response = Http::get($url);

        $mimeType = explode(';', $response->header('Content-Type'))[0];
        $ext = explode('/', $mimeType)[1];
        $path = "images/magazine_scans/{$issue->id}.{$ext}";
        Storage::disk('public')->put($path, $response->body());

        $issue->update(['imgext' => $ext]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $issue->magazine->getKey(),
            'section_name'     => $issue->magazine->name,
            'sub_section'      => 'Issue Image',
            'sub_section_id'   => $issue->getKey(),
            'sub_section_name' => $issue->issue,
        ]);

        return response(asset("storage/{$path}"), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function destroyImage(Request $request, Magazine $magazine, MagazineIssue $issue)
    {
        if ($issue->image) {
            Storage::disk('public')->delete($issue->image_path . '/' . $issue->image_file);
            $issue->update(['imgext' => null]);

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Magazines',
                'section_id'       => $issue->magazine->getKey(),
                'section_name'     => $issue->magazine->name,
                'sub_section'      => 'Issue Image',
                'sub_section_id'   => $issue->getKey(),
                'sub_section_name' => $issue->issue,
            ]);
        }

        return redirect()->route('admin.magazines.issues.edit', [
            'magazine' => $issue->magazine,
            'issue'    => $issue,
        ]);
    }
}
