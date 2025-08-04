<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Control;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameAka;
use App\Models\GameSeries;
use App\Models\GameVs;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Port;
use App\Models\ProgrammingLanguage;
use App\Models\ProgressSystem;
use App\Models\SoundHardware;
use App\Rules\Slug;
use App\View\Components\Admin\Crumb;
use Error;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    public function index()
    {
        return view('admin.games.games.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                ],
            ]);
    }

    public function edit(Game $game)
    {
        $data = $this->getReferenceData();

        return view('admin.games.games.edit')
            ->with(
                array_merge(
                    $data,
                    [
                        'breadcrumbs' => [
                            new Crumb(route('admin.games.games.index'), 'Games'),
                            new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                        ],
                        'game'                 => $game,
                    ]
                )
            );
    }

    public function create()
    {
        $data = $this->getReferenceData();

        return view('admin.games.games.edit')
            ->with(
                array_merge(
                    $data,
                    [
                        'breadcrumbs' => [
                            new Crumb(route('admin.games.games.index'), 'Games'),
                            new Crumb(route('admin.games.games.create'), 'Add new game'),
                        ],
                    ]
                )
            );
    }

    public function store(Request $request)
    {
        $this->validateGame($request, null);
        $game = Game::create([
            'game_name' => $request->name,
            'slug'      => $request->slug,
        ]);

        $this->updateBaseInfo($request, $game);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Game',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.games.edit', $game);
    }

    public function update(Request $request, Game $game)
    {
        $this->validateGame($request, $game->getKey());

        $game->update([
            'game_name'          => $request->name,
            'slug'               => $request->slug,
        ]);

        $this->updateBaseInfo($request, $game);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Game',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.games.edit', $game);
    }

    public function destroy(Game $game)
    {
        throw new Error('Game deletion is not implemented yet');
    }

    public function updateBaseInfo(Request $request, Game $game)
    {
        $game->update([
            'port_id'            => $request->port,
            'progress_system_id' => $request->progress,
            'game_series_id'     => $request->series,
        ]);

        $game->genres()->detach();
        collect($request->genres)
            ->map(function ($id) {
                return Genre::find($id);
            })
            ->each(function ($genre) use ($game) {
                $game->genres()->attach($genre);
            });

        $game->programmingLanguages()->detach();
        collect($request->languages)
            ->map(function ($id) {
                return ProgrammingLanguage::find($id);
            })
            ->each(function ($language) use ($game) {
                $game->programmingLanguages()->attach($language);
            });

        $game->engines()->detach();
        collect($request->engines)
            ->map(function ($id) {
                return Engine::find($id);
            })
            ->each(function ($engine) use ($game) {
                $game->engines()->attach($engine);
            });

        $game->controls()->detach();
        collect($request->controls)
            ->map(function ($id) {
                return Control::find($id);
            })
            ->each(function ($control) use ($game) {
                $game->controls()->attach($control);
            });

        $game->soundHardwares()->detach();
        collect($request->sound)
            ->map(function ($id) {
                return SoundHardware::find($id);
            })
            ->each(function ($hardware) use ($game) {
                $game->soundHardwares()->attach($hardware);
            });
    }

    public function updateMultiplayer(Request $request, Game $game)
    {
        $request->validate([
            'players'              => 'nullable|numeric',
            'players_linked'       => 'nullable|numeric',
            'multiplayer_type'     => ['nullable', Rule::in(Game::MULTIPLAYER_TYPES)],
            'multiplayer_hardware' => ['nullable', Rule::in(Game::MULTIPLAYER_HARDWARE)],
        ]);

        $game->update([
            'number_players_on_same_machine'   => $request->players,
            'number_players_multiple_machines' => $request->players_linked,
            'multiplayer_type'                 => $request->multiplayer_type,
            'multiplayer_hardware'             => $request->multiplayer_hardware,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Game',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.games.edit', $game);
    }

    public function storeAka(Request $request, Game $game)
    {
        $aka = GameAka::create([
            'game_id'     => $game->getKey(),
            'aka_name'    => $request->aka,
            'language_id' => $request->language,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'AKA',
            'sub_section_id'   => $aka->getKey(),
            'sub_section_name' => $aka->aka_name,
        ]);

        return redirect()->route('admin.games.games.edit', $game);
    }

    public function destroyAka(Game $game, GameAka $aka)
    {
        $aka->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $aka->game->getKey(),
            'section_name'     => $aka->game->game_name,
            'sub_section'      => 'AKA',
            'sub_section_id'   => $aka->getKey(),
            'sub_section_name' => $aka->aka_name,
        ]);

        return redirect()->route('admin.games.games.edit', $aka->game);
    }

    public function storeVs(Request $request, Game $game)
    {
        $vs = GameVs::create([
            'atari_id'      => $game->getKey(),
            'amiga_id'      => $request->amiga_id,
            'lemon64_slug'  => $request->lemon64_slug,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Vs',
            'sub_section_id'   => $vs->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return redirect()->route('admin.games.games.edit', $game);
    }

    public function destroyVs(Game $game, int $amigaId)
    {
        $query = GameVs::where('atari_id', $game->getKey())
            ->where('amiga_id', $amigaId);

        $vs = $query->firstOrFail();

        $query->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $vs->game->getKey(),
            'section_name'     => $vs->game->game_name,
            'sub_section'      => 'Vs',
            'sub_section_id'   => $vs->amiga_id,
            'sub_section_name' => $vs->game->game_name,
        ]);

        return redirect()->route('admin.games.games.edit', $vs->game);
    }

    private function validateGame(Request $request, ?int $gameId)
    {
        $request->validate([
            'name'      => 'required',
            'port'      => 'nullable|numeric',
            'genres'    => 'nullable|array',
            'languages' => 'nullable|array',
            'engines'   => 'nullable|array',
            'controls'  => 'nullable|array',
            'slug'      => [new Slug, Rule::unique('game')->ignore($gameId, 'game_id')],
        ]);
    }

    private function getReferenceData(): array
    {
        $genres = Genre::all()->sortBy('name');
        $ports = Port::all()->sortBy('name');
        $programmingLanguages = ProgrammingLanguage::all()->sortBy('name');
        $engines = Engine::all()->sortBy('name');
        $controls = Control::all()->sortBy('name');
        $progressSystems = ProgressSystem::all()->sortBy('name');
        $series = GameSeries::all()->sortBy('name');
        $languages = Language::all()->sortBy('name');
        $soundHardwares = SoundHardware::all()->sortBy('name');

        return [
            'genres'               => $genres,
            'ports'                => $ports,
            'programmingLanguages' => $programmingLanguages,
            'engines'              => $engines,
            'controls'             => $controls,
            'progressSystems'      => $progressSystems,
            'series'               => $series,
            'languages'            => $languages,
            'soundHardwares'       => $soundHardwares,
        ];
    }
}
