<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameSeries;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class GameSeriesController extends Controller
{
    public function index()
    {
        return view('admin.games.series.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.series.index'), 'Series'),
                ],
            ]);
    }

    public function edit(GameSeries $series)
    {
        return view('admin.games.series.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.series.index'), 'Series'),
                    new Crumb(route('admin.games.series.edit', $series), $series->name),
                ],
                'series' => $series,
            ]);
    }

    public function create(GameSeries $series)
    {
        return view('admin.games.series.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.series.index'), 'Series'),
                    new Crumb(route('admin.games.series.create'), 'Create new series'),
                ],
            ]);
    }

    public function update(Request $request, GameSeries $series)
    {
        $oldName = $series->name;
        $series->update(['name' => $request->name]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Game series',
            'section_id'       => $series->getKey(),
            'section_name'     => $oldName,
            'sub_section'      => 'Series',
            'sub_section_id'   => $series->getKey(),
            'sub_section_name' => $series->name,
        ]);

        return redirect()->route('admin.games.series.index');
    }

    public function store(Request $request)
    {
        $series = GameSeries::create(['name' => $request->name]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game series',
            'section_id'       => $series->getKey(),
            'section_name'     => $series->name,
            'sub_section'      => 'Series',
            'sub_section_id'   => $series->getKey(),
            'sub_section_name' => $series->name,
        ]);

        return redirect()->route('admin.games.series.edit', $series);
    }

    public function removeGame(GameSeries $series, Game $game)
    {
        $game->series()->dissociate();
        $game->save();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Game series',
            'section_id'       => $series->getKey(),
            'section_name'     => $series->name,
            'sub_section'      => 'Game',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.series.edit', $series);
    }

    public function addGame(Request $request, GameSeries $series)
    {
        $game = Game::find($request->game);

        $game->series()->associate($series);
        $game->save();

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Game series',
            'section_id'       => $series->getKey(),
            'section_name'     => $series->name,
            'sub_section'      => 'Game',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.series.edit', $series);
    }
}
