<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Game;
use App\Models\GameSubmitInfo;
use App\Models\Genre;
use App\Models\PublisherDeveloper;
use App\Models\Review;
use App\Models\Screenshot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameController extends Controller
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
            $games->where('game_name', 'like', '%'.$request->input('title').'%');
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

        $referenceData = $this->getSearchReferenceData();

        return view('games.search')->with(
            array_merge($referenceData, [
                'games'  => $games,
                'export' => $request->boolean('export'),
            ])
        );
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
                    return asset('storage/images/game_release_scans/'.$boxscan->file);
                });
            });
        $gameBoxscans = $game->boxscans->map(function ($boxscan) {
            return asset('storage/images/game_boxscans/'.$boxscan->file);
        });

        // Collect interviews for individuals of the game. An individual may
        // have multiple role so make sure to get unique interviews
        $interviews = $game->individuals
            ->flatMap(function ($gameIndividual) {
                return $gameIndividual->individual->interviews;
            })
            ->unique('interview_id');

        // Filter unpublishes reviews
        $reviews = $game->reviews->reject(function ($review) {
            return $review->review_edit !== Review::REVIEW_PUBLISHED;
        });

        return view('games.show')->with([
            'game'              => $game,
            'developersLogos'   => $developersLogos,
            'boxscans'          => $releaseBoxscans->merge($gameBoxscans),
            'interviews'        => $interviews,
            'reviews'           => $reviews,
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

    public function submitInfo(Game $game, Request $request)
    {
        $info = new GameSubmitInfo();
        $info->timestamp = time();
        $info->submit_text = $request->info;
        $info->game_done = GameSubmitInfo::SUBMISSION_NEW;
        $info->game()->save($game);

        $game->infoSubmissions()->save($info);
        $request->user()->gameSubmissions()->save($info);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $screenshot = new Screenshot();
                $screenshot->imgext = $file->extension();

                $info->screenshots()->save($screenshot);

                $file->storeAs('images/game_submit_screenshots', $screenshot->screenshot_id.'.'.$screenshot->imgext, 'public');
            }
        }

        $request->session()->flash('alert-title', 'Info submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

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
