<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\Models\GameSubmitInfo;
use App\View\Components\Admin\Crumb;

class GameSubmissionController extends Controller
{
    public function index()
    {
        return view('admin.games.submissions.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.submissions.index'), 'Game submissions'),
                ],
            ]);
    }

    public function edit(GameSubmitInfo $info)
    {
        return view('admin.games.submissions.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.submissions.index'), 'Games submissions'),
                    new Crumb(route('admin.games.submissions.edit', $info), $info->game->game_name),
                ],
                'info'        => $info,
            ]);
    }
}
