<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\PublisherDeveloper;
use App\Models\PublisherDeveloperText;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GameCompanyController extends Controller
{
    public function index()
    {
        return view('admin.games.companies.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.companies.index'), 'Companies'),
                ],
            ]);
    }

    public function create()
    {
        return view('admin.games.companies.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.companies.index'), 'Companies'),
                    new Crumb(route('admin.games.companies.create'), 'Create'),
                ],
            ]);
    }

    public function edit(PublisherDeveloper $company)
    {
        return view('admin.games.companies.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.companies.index'), 'Companies'),
                    new Crumb(route('admin.games.companies.edit', $company), $company->pub_dev_name),
                ],
                'company'  => $company,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', Rule::unique('pub_dev', 'pub_dev_name')],
        ]);

        $company = new PublisherDeveloper(['pub_dev_name' => $request->name]);
        $company->save();

        $ext = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logo->storeAs('images/company_logos/', $company->pub_dev_id.'.'.$logo->extension(), 'public');
            $ext = $logo->extension();

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Company',
                'section_id'       => $company->getKey(),
                'section_name'     => $company->pub_dev_name,
                'sub_section'      => 'Logo',
                'sub_section_id'   => $company->getKey(),
                'sub_section_name' => $company->pub_dev_name,
            ]);
        }

        $text = new PublisherDeveloperText([
            'pub_dev_profile' => $request->profile,
            'pub_dev_imgext'  => $ext,
        ]);
        $company->text()->save($text);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Company',
            'section_id'       => $company->getKey(),
            'section_name'     => $company->pub_dev_name,
            'sub_section'      => 'Company',
            'sub_section_id'   => $company->getKey(),
            'sub_section_name' => $company->pub_dev_name,
        ]);

        return redirect()->route('admin.games.companies.edit', $company);
    }

    public function update(Request $request, PublisherDeveloper $company)
    {
        $request->validate([
            'name' => ['required', Rule::unique('pub_dev', 'pub_dev_name')->ignore($company->pub_dev_id, 'pub_dev_id')],
        ]);

        $ext = null;
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $logo->storeAs('images/company_logos/', $company->pub_dev_id.'.'.$logo->extension(), 'public');
            $ext = $logo->extension();

            ChangelogHelper::insert([
                'action'           => Changelog::UPDATE,
                'section'          => 'Company',
                'section_id'       => $company->getKey(),
                'section_name'     => $company->pub_dev_name,
                'sub_section'      => 'Logo',
                'sub_section_id'   => $company->getKey(),
                'sub_section_name' => $company->pub_dev_name,
            ]);
        }

        $company->update([
            'pub_dev_name' => $request->name,
        ]);

        $attrs = [
            'pub_dev_profile' => $request->profile,
            'pub_dev_imgext'  => $ext,
        ];

        if ($company->text) {
            $company->text->update($attrs);
        } else {
            $text = new PublisherDeveloperText($attrs);
            $company->text()->save($text);
        }

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Company',
            'section_id'       => $company->getKey(),
            'section_name'     => $company->pub_dev_name,
            'sub_section'      => 'Company',
            'sub_section_id'   => $company->getKey(),
            'sub_section_name' => $company->pub_dev_name,
        ]);

        return redirect()->route('admin.games.companies.index');
    }

    public function destroy(PublisherDeveloper $company)
    {
        $this->destroyLogo($company);
        $company->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Company',
            'section_id'       => $company->getKey(),
            'section_name'     => $company->pub_dev_name,
            'sub_section'      => 'Company',
            'sub_section_id'   => $company->getKey(),
            'sub_section_name' => $company->pub_dev_name,
        ]);

        return redirect()->route('admin.games.companies.index');
    }

    public function destroyLogo(PublisherDeveloper $company)
    {
        if ($company->logo) {
            Storage::disk('public')->delete('images/company_logos/'.$company->pub_dev_id.'.'.$company->text->pub_dev_imgext);
            $company->text->pub_dev_imgext = null;
            $company->text->save();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Company',
                'section_id'       => $company->getKey(),
                'section_name'     => $company->pub_dev_name,
                'sub_section'      => 'Logo',
                'sub_section_id'   => $company->getKey(),
                'sub_section_name' => $company->pub_dev_name,
            ]);
        }

        return redirect()->route('admin.games.companies.edit', $company);
    }
}
