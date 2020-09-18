<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Game;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
{
    public function index()
    {
        $gamesCount = DB::table('game')->count();

        return view('games.index')->with([
            'gamesCount' => $gamesCount,
            'updates'    => $this->getUpdates(),
        ]);
    }

    public function search(Request $request)
    {
        $games = Game::select();

        if ($request->filled('title')) {
            $games->where('game_name', 'like', '%' . $request->input('title') . '%');
        }

        if ($request->filled('developer')) {
            $games->whereHas('developers', function (Builder $query) use ($request) {
                $query->where('pub_dev_name', 'like', '%' . $request->input('developer') . '%');
            });
        }

        if ($request->filled('publisher')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereHas('publisher', function (Builder $query2) use ($request) {
                    $query2->where('pub_dev_name', 'like', '%' . $request->input('publisher') . '%');
                });
            });
        }

        if ($request->filled('genre')) {
            $games->whereHas('genres', function (Builder $query) use ($request) {
                $query->where('name', 'like', '%' . $request->input('genre') . '%');
            });
        }

        if ($request->filled('year')) {
            $games->whereHas('releases', function (Builder $query) use ($request) {
                $query->whereYear('date', $request->input('year'));
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
            ->orderBy('game_name')
            ->paginate(12);

        return view('games.search')->with([
            'games' => $games,
        ]);
    }

    public function show(Game $game)
    {
        $developersLogos = $game->developers
            ->filter(function ($developer) {
                return $developer->texts->isNotEmpty() && $developer->texts->first()->file !== null;
            })
            ->map(function ($developer) {
                return $developer->texts->first()->file;
            });

        // Collect all release scans and game scans in a single list
        // This is temporary until all game scans are moved to releases
        $releaseBoxscans = $game->releases
            ->flatMap(function ($release) {
                return $release->boxscans->map(function ($boxscan) {
                    return asset('storage/images/game_release_scans/' . $boxscan->file);
                });
            });
        $gameBoxscans = $game->boxscans->map(function ($boxscan) {
            return asset('storage/images/game_boxscans/' . $boxscan->file);
        });

        // Collect interviews for individuals of the game. An individual may
        // have multiple role so make sure to get unique interviews
        $interviews = $game->individuals
            ->flatMap(function ($gameIndividual) {
                return $gameIndividual->individual->interviews;
            })
            ->unique('interview_id');

        return view('games.show')->with([
            'game'              => $game,
            'developersLogos'   => $developersLogos,
            'boxscans'          => $releaseBoxscans->merge($gameBoxscans),
            'interviews'        => $interviews,
        ]);
    }

    public function postComment(Game $game, Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $game->comments()->save($comment);

        return back();
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
}
