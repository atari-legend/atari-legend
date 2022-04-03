<?php

namespace App\Http\Controllers;

use App\Models\Engine;
use App\Models\Game;
use App\Models\Genre;
use App\Models\Individual;
use App\Models\MenuSoftware;
use App\Models\PublisherDeveloper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameSearchController extends Controller
{
    const PAGE_SIZE = 48;

    public function index()
    {
        $gamesCount = DB::table('game')->count();

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
        $games = Game::select();
        $software = MenuSoftware::select();

        // Boolean to check if a search on software can be made
        // Software search only works via title or titleAZ. If neither
        // are used then there should be no software search results
        $softwareSearchPossible = false;
        // Similar for games, we need at least one search criteria
        // to trigger a search
        $searchPossible = false;

        if ($request->filled('titleAZ')) {
            if ($request->input('titleAZ') === '0-9') {
                $games->where('game_name', 'regexp', '^[0-9]+');
                $software->where('name', 'regexp', '^[0-9]+');
            } else {
                $games->where('game_name', 'like', $request->input('titleAZ') . '%');
                $software->where('name', 'like', $request->input('titleAZ') . '%');
            }
            $searchPossible = true;
            $softwareSearchPossible = true;
        }

        if ($request->filled('title')) {
            // Search main game name or an AKA
            $games->where(function (Builder $query) use ($request) {
                $query->where('game_name', 'like', '%' . $request->input('title') . '%')
                    ->orWhereHas('akas', function (Builder $subQuery) use ($request) {
                        $subQuery->where('aka_name', 'like', '%' . $request->input('title') . '%');
                    });
            });
            $searchPossible = true;

            $software->where('name', 'like', '%' . $request->title . '%');
            $softwareSearchPossible = true;
        }

        if ($request->filled('developer')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('pub_dev_name', 'like', '%' . $request->input('developer') . '%');
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('developer_id')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('pub_dev_id', $request->input('developer_id'));
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('publisher')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('pub_dev_name', 'like', '%' . $request->input('publisher') . '%');
                });
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('publisher_id')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('pub_dev_id', $request->input('publisher_id'));
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
                    ->pluck('ind_id')
                    ->concat($individual->individuals->pluck('ind_id'))
                    ->concat(collect($individual->ind_id));
                $games->whereHas('individuals', function (Builder $query) use ($ids) {
                    $query->whereIn('individual_id', $ids);
                });
            }
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('review')) {
            $games->has('reviews');
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('download')) {
            $games->whereHas('releases', function (Builder $query) {
                $query->whereHas('medias', function (Builder $query2) {
                    $query2->has('dumps');
                });
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('screenshot')) {
            $games->has('screenshots');
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('boxscan')) {
            $games->whereHas('releases', function (Builder $query) {
                $query->has('boxscans');
            });
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if ($request->filled('music')) {
            $games->has('sndhs');
            $searchPossible = true;
            $softwareSearchPossible = false;
        }

        if (! $searchPossible) {
            // Force no game results when there were no search
            // constraints
            $games->where('game_id', '<', 0);
        }

        $games = $games
            ->orderBy('game_name');

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
            && strtolower($games->first()->game_name) === strtolower($request->title)) {
            return redirect()->route('games.show', $games->first());
        }

        $referenceData = $this->getSearchReferenceData();

        return view('games.search')->with(
            array_merge($referenceData, [
                'games'        => $games,
                'title'        => $request->title,
                'titleAZ'      => $request->titleAZ,
                'publisher'    => $request->publisher,
                'publisher_id' => $request->publisher_id,
                'developer'    => $request->developer,
                'developer_id' => $request->developer_id,
                'year'         => $request->year,
                'year_id'      => $request->year_id,
                'export'       => $request->boolean('export'),
                'software'     => $software,
            ])
        );
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
            $updates[$from->getTimestamp()] = DB::table('change_log')
                ->whereBetween('timestamp', [$from->getTimestamp(), $to->getTimestamp()])
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
        $companies = PublisherDeveloper::all()
            ->sortBy('pub_dev_name');

        $years = DB::table('game_release')
            ->selectRaw('CAST(YEAR(date) AS CHAR) as year')
            ->distinct('YEAR(date)')
            ->whereNotNull('date')
            ->where('date', '!=', 0)
            ->orderBy('year')
            ->get();

        $genres = Genre::all()
            ->sortBy('name');

        $individuals = Individual::all()
            ->sortBy('ind_name');

        $engines = Engine::all()
            ->sortBy('name');

        return [
            'companies'   => $companies,
            'years'       => $years,
            'genres'      => $genres,
            'individuals' => $individuals,
            'engines'     => $engines,
        ];
    }
}
