<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\View\Components\Admin\Crumb;

class GameIndividualController extends Controller
{
    public function index()
    {
        return view('admin.games.individuals.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.individuals.index'), 'Individuals'),
                ],
            ]);
    }
}
