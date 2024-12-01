<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameFact;
use App\Models\Screenshot;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameFactsController extends Controller
{
    public function index(Game $game)
    {
        return view('admin.games.games.facts.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-facts.index', $game), 'Facts'),
                ],
                'game'        => $game,
            ]);
    }

    public function edit(Game $game, GameFact $fact)
    {
        return view('admin.games.games.facts.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-facts.index', $game), 'Facts'),
                    new Crumb('', $fact->getKey()),
                ],
                'game'        => $game,
                'fact'        => $fact,
            ]);
    }

    public function create(Game $game)
    {
        return view('admin.games.games.facts.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-facts.index', $game), 'Facts'),
                    new Crumb('', 'Add fact'),
                ],
                'game'        => $game,
            ]);
    }

    public function update(Game $game, GameFact $fact, Request $request)
    {
        $request->validate([
            'content'     => 'required',
        ]);

        $fact->update([
            'game_fact' => $request->content,
        ]);

        if ($request->remove_file || $request->hasFile('file')) {
            $this->destroyImages($fact);
        }

        $this->storeImage($request, $fact);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Games',
            'section_id'       => $fact->game->getKey(),
            'section_name'     => $fact->game->game_name,
            'sub_section'      => 'Fact',
            'sub_section_id'   => $fact->getKey(),
            'sub_section_name' => $fact->game->game_name,
        ]);

        return redirect()->route('admin.games.game-facts.index', ['game' => $fact->game]);
    }

    public function store(Game $game, Request $request)
    {
        $request->validate([
            'content'     => 'required',
        ]);

        $fact = new GameFact([
            'game_fact' => $request->content,
        ]);
        $game->facts()->save($fact);

        $this->storeImage($request, $fact);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Fact',
            'sub_section_id'   => $fact->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.game-facts.index', ['game' => $game]);
    }

    public function destroy(Game $game, GameFact $fact)
    {
        $this->destroyImages($fact);

        $fact->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Fact',
            'sub_section_id'   => $fact->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.game-facts.index', ['game' => $game]);
    }

    private function destroyImages(GameFact $fact)
    {
        foreach ($fact->screenshots as $screenshot) {
            Storage::disk('public')->delete($screenshot->getPath('game_fact'));
            $screenshot->delete();
        }
    }

    private function storeImage(Request $request, GameFact $fact)
    {
        if ($request->hasFile('file')) {
            foreach ($request->file('file') as $file) {
                $screenshot = Screenshot::create([
                    'imgext' => strtolower($file->extension()),
                ]);
                $file->storeAs($screenshot->getFolder('game_fact'), $screenshot->screenshot_id . '.' . $screenshot->imgext, 'public');

                $fact->screenshots()->attach($screenshot);
            }
        }
    }
}
