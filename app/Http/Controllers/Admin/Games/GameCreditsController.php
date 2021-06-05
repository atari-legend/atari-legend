<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Individual;
use App\Models\IndividualRole;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameCreditsController extends Controller
{
    public function index(Game $game)
    {
        $roles = IndividualRole::select()
            ->orderBy('name')
            ->get();

        return view('admin.games.games.credits.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-credits.index', $game), 'Credits'),
                ],
                'game'        => $game,
                'roles'       => $roles,
            ]);
    }

    public function store(Request $request, Game $game)
    {
        $individual = Individual::find($request->individual);
        if ($individual !== null) {
            $individual->games()->attach($game, ['individual_role_id' => $request->role]);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Games',
                'section_id'       => $game->getKey(),
                'section_name'     => $game->game_name,
                'sub_section'      => 'Creator',
                'sub_section_id'   => $individual->ind_id,
                'sub_section_name' => $individual->ind_name,
            ]);
        }

        return redirect()->route('admin.games.game-credits.index', $game);
    }

    public function destroy(Request $request, Game $game, Individual $individual)
    {
        DB::table('game_individual')
            ->where('game_id', $game->game_id)
            ->where('individual_id', $individual->ind_id)
            ->where('individual_role_id', $request->role)
            ->delete();


        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Creator',
            'sub_section_id'   => $individual->getKey(),
            'sub_section_name' => $individual->ind_name,
        ]);

        return redirect()->route('admin.games.game-credits.index', $game);
    }
}
