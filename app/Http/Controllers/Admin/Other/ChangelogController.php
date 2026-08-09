<?php

namespace App\Http\Controllers\Admin\Other;

use App\Http\Controllers\Controller;
use App\View\Components\Admin\Crumb;

class ChangelogController extends Controller
{
    public function index()
    {
        return view('admin.others.changelog.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.changelog.index'), 'Changelog'),
                ],
            ]);
    }
}
