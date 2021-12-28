<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Individual;
use App\Models\IndividualText;
use App\Models\PublisherDeveloper;
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

        // Don't forget the changelog!

        return redirect()->route('admin.games.companies.edit', $company);
    }

    public function update(Request $request, PublisherDeveloper $company)
    {

        // Don't forget the changelog!

        return redirect()->route('admin.games.companies.index');
    }

    public function destroy(PublisherDeveloper $company)
    {

        // Delete logo

        // Don't forget the changelog!

        return redirect()->route('admin.games.companies.index');
    }

    public function destroyAvatar(PublisherDeveloper $company)
    {

        // Changelog?

        return redirect()->route('admin.games.companies.edit', $company);
    }
}
