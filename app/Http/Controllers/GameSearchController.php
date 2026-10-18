<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Company;
use App\Models\Control;
use App\Models\Engine;
use App\Models\Game;
use App\Models\GameSeries;
use App\Models\Genre;
use App\Models\Individual;
use App\Models\MenuSoftware;
use App\Models\ProgrammingLanguage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameSearchController extends Controller
{
    const PAGE_SIZE = 48;

    /**
     * The yes/no criteria on what a game has, keyed on their query parameter:
     * the relations of which at least one has to exist, and the label shown
     * for it.
     */
    const FLAGS = [
        'screenshot' => [['screenshots'], 'Screenshot'],
        'boxscan'    => [['releases.boxscans'], 'Boxscan'],
        'music'      => [['sndhs'], 'Music'],
        'video'      => [['videos'], 'Video'],
        'download'   => [['releases.medias.dumps'], 'Downloadable / playable'],
        'review'     => [['reviews'], 'Review'],
        'magazine'   => [['magazineIndices'], 'Magazine coverage'],
        // A game is on a menu disk through one of its releases, or directly
        // for the disks that carry something other than the game itself
        // (docs, trainers, ...)
        'menu'       => [['releases.menuDiskContents', 'menuDiskContents'], 'On a menu disk'],
    ];

    /**
     * The choices of the minimum number of players.
     */
    const PLAYERS = [2, 3, 4];

    public function index()
    {
        $gamesCount = DB::table('games')->count();

        $referenceData = $this->getSearchReferenceData();

        return view('games.index')->with(
            array_merge($referenceData, [
                'gamesCount' => $gamesCount,
                'updates'    => $this->getUpdates(),
            ])
        );
    }

    public function search(Request $request)
    {
        $games = Game::select('games.*')
            // The results walk them for the play overlay and the dumps icon
            ->with('releases.medias.dumps');
        $software = MenuSoftware::select('menu_software.*');

        // Boolean to check if a search on software can be made
        // Software search only works via title or titleAZ. If neither
        // are used then there should be no software search results
        $softwareSearchPossible = false;
        // Similar for games, we need at least one search criteria
        // to trigger a search
        $searchPossible = false;

        if ($request->filled('titleAZ')) {
            Helper::whereTitleStartsWith($games, 'games.name', $request->input('titleAZ'));
            Helper::whereTitleStartsWith($software, 'name', $request->input('titleAZ'));
            $searchPossible = true;
            $softwareSearchPossible = true;
        }

        if ($request->filled('title')) {
            // Search main game name or an AKA
            $games->where(function (Builder $query) use ($request) {
                $query->where('games.name', 'like', '%' . $request->input('title') . '%')
                    ->orWhereHas('akas', function (Builder $subQuery) use ($request) {
                        $subQuery->where('name', 'like', '%' . $request->input('title') . '%');
                    });
            });
            $searchPossible = true;

            $software->where('name', 'like', '%' . $request->title . '%');
            $softwareSearchPossible = true;
        }

        if ($request->filled('developer')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('developer') . '%');
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('developer_id')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('companies.id', $request->input('developer_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('publisher')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('name', 'like', '%' . $request->input('publisher') . '%');
                });
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('publisher_id')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('companies.id', $request->input('publisher_id'));
                });
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('genre')) {
            $games->whereHas('genres', function (Builder $query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('genre') . '%');
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('genre_id')) {
            $games->whereHas('genres', function (Builder $query) use ($request) {
                $query->where('id', $request->input('genre_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('engine')) {
            $games->whereHas('engines', function (Builder $query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('engine') . '%');
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('engine_id')) {
            $games->whereHas('engines', function (Builder $query) use ($request) {
                $query->where('id', $request->input('engine_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('year')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereYear('date', $request->input('year'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('year_id')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereYear('date', $request->input('year_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('individual_id') || $request->filled('individual_select')) {
            // When searching for individuals we should consider their nicks
            // and also the case where a nick was associated with the game
            // instead of the individual
            $individual = Individual::find($request->individual_id ?? $request->individual_select);
            if ($individual) {
                // Collect all IDs for this individual: That include the IDs
                // of their nicks, or if they are a nick themselves the ID
                // of the "parent" individual
                $ids = $individual
                    ->nicknames
                    ->pluck('id')
                    ->concat($individual->individuals->pluck('id'))
                    ->concat(collect($individual->getKey()));
                $games->whereHas('individuals', function (Builder $query) use ($ids) {
                    $query->whereIn('individual_id', $ids);
                });
            }
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        // Yes/no criteria: "1" (or a legacy checkbox's "on") keeps the games
        // that have the thing, "0" the ones that don't. Both are filled.
        foreach (self::FLAGS as $param => [$relations]) {
            if ($request->filled($param)) {
                if ($request->boolean($param)) {
                    $games->where(function (Builder $query) use ($relations) {
                        foreach ($relations as $relation) {
                            $query->orHas($relation);
                        }
                    });
                } else {
                    foreach ($relations as $relation) {
                        $games->doesntHave($relation);
                    }
                }
                $searchPossible = true;
                $softwareSearchPossible = false;
            }
        }

        if ($request->filled('year_from') || $request->filled('year_to')) {
            // A single release in the range is enough
            $games->whereHas('releases', function (Builder $query) use ($request) {
                if ($request->filled('year_from')) {
                    $query->whereYear('date', '>=', $request->input('year_from'));
                }
                if ($request->filled('year_to')) {
                    $query->whereYear('date', '<=', $request->input('year_to'));
                }
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('series_id')) {
            $games->where('games.game_series_id', $request->input('series_id'));
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('programming_language_id')) {
            $games->whereHas('programmingLanguages', function (Builder $query) use ($request) {
                $query->where('programming_languages.id', $request->input('programming_language_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('control_id')) {
            $games->whereHas('controls', function (Builder $query) use ($request) {
                $query->where('controls.id', $request->input('control_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('players')) {
            // On the same machine, or across linked ones
            $games->where(function (Builder $query) use ($request) {
                $query->where('games.players_same_machine', '>=', $request->integer('players'))
                    ->orWhere('games.players_multiple_machines', '>=', $request->integer('players'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('multiplayer')) {
            $games->where('games.multiplayer_type', $request->input('multiplayer'));
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('license')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->where('license', $request->input('license'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('ai')) {
            // Both ways: "0" is filled, and finds the games that do not
            $games->where('games.uses_ai', $request->boolean('ai'));
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if (! $searchPossible) {
            // Force no game results when there were no search
            // constraints
            $games->where('games.id', '<', 0);
        }

        $games = $games
            ->orderBy('games.name');

        if (! $softwareSearchPossible) {
            // Force no software results when there were no titles selected
            $software->where('id', '<', 0);
        }

        $software = $software
            ->orderBy('name')
            ->paginate(MenuSetController::PAGE_SIZE);

        if (! $request->boolean('export')) {
            $games = $games->paginate(GameSearchController::PAGE_SIZE);
        } else {
            $games = $games->get();
        }

        // If only one match with the requested title, redirect to it
        if ($games->count() === 1
            && $request->filled('title')
            && strtolower($games->first()->name) === strtolower($request->title)) {
            return redirect()->route('games.show', $games->first());
        }

        return view('games.search')->with([
            'games'    => $games,
            'title'    => $request->title,
            'titleAZ'  => $request->titleAZ,
            'export'   => $request->boolean('export'),
            'software' => $software,
            'criteria' => $this->getCriteria($request),
        ]);
    }

    /**
     * Describe the criteria a search was made with, so that the results page
     * can show them.
     *
     * Each criterion comes with the query parameters that carry it, so that it
     * can be removed from the search on its own. Criteria picked from a
     * dropdown are resolved to their name rather than shown as an ID.
     *
     * @return array List of criteria, each with a `label`, a `value` (a bool
     *               for the yes/no ones, which the results page can flip) and
     *               the `params` that carry it
     */
    public function getCriteria(Request $request): array
    {
        $criteria = [];

        $add = function (string $label, string|bool $value, array $params) use (&$criteria) {
            $criteria[] = [
                'label'  => $label,
                'value'  => $value,
                'params' => $params,
            ];
        };

        if ($request->filled('titleAZ')) {
            $add('Title starts with', $request->input('titleAZ'), ['titleAZ']);
        }
        if ($request->filled('title')) {
            $add('Title', $request->input('title'), ['title']);
        }

        foreach (['publisher' => 'Publisher', 'developer' => 'Developer'] as $param => $label) {
            if ($request->filled($param)) {
                $add($label, $request->input($param), [$param]);
            }
            if ($request->filled("{$param}_id")) {
                $add($label, Company::find($request->input("{$param}_id"))?->name ?? 'Unknown', ["{$param}_id"]);
            }
        }

        if ($request->filled('individual_id') || $request->filled('individual_select')) {
            $individual = Individual::find($request->individual_id ?? $request->individual_select);
            $add('Involving', $individual?->name ?? 'Unknown', ['individual', 'individual_select', 'individual_id']);
        }

        if ($request->filled('year')) {
            $add('Release year', $request->input('year'), ['year']);
        }
        if ($request->filled('year_id')) {
            $add('Release year', $request->input('year_id'), ['year_id']);
        }
        if ($request->filled('year_from') || $request->filled('year_to')) {
            $from = $request->input('year_from');
            $to = $request->input('year_to');
            $add('Release year', match (true) {
                $from === $to  => $from,
                $to === null   => "{$from} or later",
                $from === null => "{$to} or earlier",
                default        => "{$from}–{$to}",
            }, ['year_from', 'year_to']);
        }

        foreach (['genre' => [Genre::class, 'Genre'], 'engine' => [Engine::class, 'Engine']] as $param => [$model, $label]) {
            if ($request->filled($param)) {
                $add($label, $request->input($param), [$param]);
            }
            if ($request->filled("{$param}_id")) {
                $add($label, $model::find($request->input("{$param}_id"))?->name ?? 'Unknown', ["{$param}_id"]);
            }
        }

        $lookups = [
            'series_id'               => [GameSeries::class, 'Series'],
            'programming_language_id' => [ProgrammingLanguage::class, 'Programming language'],
            'control_id'              => [Control::class, 'Controls'],
        ];
        foreach ($lookups as $param => [$model, $label]) {
            if ($request->filled($param)) {
                $add($label, $model::find($request->input($param))?->name ?? 'Unknown', [$param]);
            }
        }

        if ($request->filled('players')) {
            $add('Players', $request->integer('players') . ' or more', ['players']);
        }
        if ($request->filled('multiplayer')) {
            $add('Multiplayer', $request->input('multiplayer'), ['multiplayer']);
        }

        foreach (self::FLAGS as $param => [, $label]) {
            if ($request->filled($param)) {
                $add($label, $request->boolean($param), [$param]);
            }
        }

        if ($request->filled('license')) {
            $add('License', $request->input('license'), ['license']);
        }
        if ($request->filled('ai')) {
            $add('Uses AI', $request->boolean('ai'), ['ai']);
        }

        return $criteria;
    }

    /**
     * Compute the number of updates per month, from the changelog table.
     *
     * @return array Map where the key is the start of the month, as a timestamp,
     *               and the value the number of updates for the month
     */
    public function getUpdates()
    {
        $to = new Carbon('last day of this month');
        $to->setTime(23, 59, 59);
        $from = new Carbon('first day of this month');
        $from->setTime(0, 0, 0);

        $updates = [];
        for ($month = 1; $month <= 12; $month++) {
            $updates[$from->getTimestamp()] = DB::table('changelogs')
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $from->subMonth();
            $to->subMonth();
        }

        return $updates;
    }

    /**
     * Get the reference data used to populate the various dropdowns of the
     * search form.
     *
     * @return array Reference data
     */
    public function getSearchReferenceData()
    {
        $companies = Company::all()
            ->sortBy('name');

        // `substr()` rather than `YEAR()` as the latter is MySQL-only, and the
        // test suite runs against SQLite. Dates are `YYYY-MM-DD` on both
        // engines, so the first four characters are the year.
        $years = DB::table('game_releases')
            ->selectRaw('substr(date, 1, 4) as year')
            ->distinct()
            ->whereNotNull('date')
            ->where('date', '!=', 0)
            ->orderBy('year')
            ->get();

        $genres = Genre::all()
            ->sortBy('name');

        $individuals = Individual::all()
            ->sortBy('name');

        $engines = Engine::all()
            ->sortBy('name');

        return [
            'companies'            => $companies,
            'years'                => $years,
            'genres'               => $genres,
            'individuals'          => $individuals,
            'engines'              => $engines,
            'series'               => GameSeries::orderBy('name')->get(),
            'programmingLanguages' => ProgrammingLanguage::orderBy('name')->get(),
            'controls'             => Control::orderBy('name')->get(),
        ];
    }
}
