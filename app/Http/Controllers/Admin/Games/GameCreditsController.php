<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\DeveloperRole;
use App\Models\Game;
use App\Models\Individual;
use App\Models\IndividualRole;
use App\Models\PublisherDeveloper;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameCreditsController extends Controller
{
    public function index(Game $game)
    {
        $roles = IndividualRole::select('individual_role.*')
            ->orderBy('name')
            ->get();
        $developerRoles = DeveloperRole::select('developer_role.*')
            ->orderBy('name')
            ->get();

        return view('admin.games.games.credits.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-credits.index', $game), 'Credits'),
                ],
                'game'           => $game,
                'roles'          => $roles,
                'developerRoles' => $developerRoles,
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
                'sub_section_id'   => $individual->getKey(),
                'sub_section_name' => $individual->ind_name,
            ]);
        }

        return redirect()->route('admin.games.game-credits.index', $game);
    }

    public function destroy(Request $request, Game $game, Individual $individual)
    {
        DB::table('game_individual')
            ->where('game_id', $game->getKey())
            ->where('individual_id', $individual->getKey())
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

    public function storeDeveloper(Request $request, Game $game)
    {
        $developer = PublisherDeveloper::find($request->developer);
        if ($developer !== null) {
            $developer->games()->attach($game, ['developer_role_id' => $request->role]);

            ChangelogHelper::insert([
                'action'           => Changelog::INSERT,
                'section'          => 'Games',
                'section_id'       => $game->getKey(),
                'section_name'     => $game->game_name,
                'sub_section'      => 'Developer',
                'sub_section_id'   => $developer->getKey(),
                'sub_section_name' => $developer->pub_dev_name,
            ]);
        }

        return redirect()->route('admin.games.game-credits.index', $game);
    }

    public function destroyDeveloper(Request $request, Game $game, PublisherDeveloper $developer)
    {
        DB::table('game_developer')
            ->where('game_id', $game->getKey())
            ->where('dev_pub_id', $developer->getKey())
            ->where('developer_role_id', $request->role)
            ->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Developer',
            'sub_section_id'   => $developer->getKey(),
            'sub_section_name' => $developer->pub_dev_name,
        ]);

        return redirect()->route('admin.games.game-credits.index', $game);
    }
}
