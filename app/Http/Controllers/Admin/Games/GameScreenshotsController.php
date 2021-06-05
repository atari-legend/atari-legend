<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Screenshot;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameScreenshotsController extends Controller
{
    public function index(Game $game)
    {
        return view('admin.games.games.screenshots.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-screenshots.index', $game), 'Screenshots'),
                ],
                'game'        => $game,
            ]);
    }

    public function store(Request $request, Game $game)
    {
        $request->validate([
            'screenshot'   => 'required',
            'screenshot.*' => 'image',
        ]);

        if ($request->hasFile('screenshot')) {
            foreach ($request->screenshot as $screenshotFile) {
                $screenshot = Screenshot::create([
                    'imgext' => strtolower($screenshotFile->extension()),
                ]);

                $screenshotFile->storeAs($screenshot->getFolder('game'), $screenshot->file, 'public');

                $game->screenshots()->attach($screenshot);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Games',
                    'section_id'       => $game->getKey(),
                    'section_name'     => $game->game_name,
                    'sub_section'      => 'Screenshot',
                    'sub_section_id'   => $screenshot->getKey(),
                    'sub_section_name' => $game->game_name,
                ]);
            }
        }

        return redirect()->route('admin.games.game-screenshots.index', $game);
    }

    public function destroy(Game $game, Screenshot $screenshot)
    {
        $game->screenshots()->detach($screenshot);

        Storage::disk('public')->delete($screenshot->getPath('game'));

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Screenshot',
            'sub_section_id'   => $screenshot->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.game-screenshots.index', $game);
    }
}
