<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Genre;
use App\Models\PublisherDeveloper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameSearchController extends Controller
{
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

        if ($request->filled('titleAZ')) {
            if ($request->input('titleAZ') === '0-9') {
                $games->where('game_name', 'regexp', '^[0-9]+');
            } else {
                $games->where('game_name', 'like', $request->input('titleAZ').'%');
            }
        }

        if ($request->filled('title')) {
            $games->where('game_name', 'like', '%'.$request->input('title').'%')
                ->orWhereHas('akas', function (Builder $query) use ($request) {
                    $query->where('aka_name', 'like', '%'.$request->input('title').'%');
                });
        }

        if ($request->filled('developer')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('pub_dev_name', 'like', '%'.$request->input('developer').'%');
            });
        }

        if ($request->filled('developer_id')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('pub_dev_id', $request->input('developer_id'));
            });
        }

        if ($request->filled('publisher')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('pub_dev_name', 'like', '%'.$request->input('publisher').'%');
                });
            });
        }

        if ($request->filled('publisher_id')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('pub_dev_id', $request->input('publisher_id'));
                });
            });
        }

        if ($request->filled('genre')) {
            $games->whereHas('genres', function (Builder $query) use ($request) {
                $query->where('name', 'like', '%'.$request->input('genre').'%');
            });
        }

        if ($request->filled('genre_id')) {
            $games->whereHas('genres', function (Builder $query) use ($request) {
                $query->where('id', $request->input('genre_id'));
            });
        }

        if ($request->filled('year')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereYear('date', $request->input('year'));
            });
        }

        if ($request->filled('year_id')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereYear('date', $request->input('year_id'));
            });
        }

        if ($request->filled('individual_id')) {
            $games->whereHas('individuals', function (Builder $query) use ($request) {
                $query->where('individual_id', $request->input('individual_id'));
            });
        }

        if ($request->filled('review')) {
            $games->has('reviews');
        }

        if ($request->filled('screenshot')) {
            $games->has('screenshots');
        }

        if ($request->filled('boxscan')) {
            $games->whereHas('releases', function (Builder $query) {
                $query->has('boxscans');
            });
        }

        $games = $games
            ->orderBy('game_name');

        if (!$request->boolean('export')) {
            $games = $games->paginate(12);
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
                'publisher'    => $request->publisher,
                'publisher_id' => $request->publisher_id,
                'developer'    => $request->developer,
                'developer_id' => $request->developer_id,
                'year'         => $request->year,
                'year_id'      => $request->year_id,
                'export'       => $request->boolean('export'),
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
        $to = Carbon::now();
        $from = new Carbon('first day of this month');

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

        return [
            'companies' => $companies,
            'years'     => $years,
            'genres'    => $genres,
        ];
    }
}
