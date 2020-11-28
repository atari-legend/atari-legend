<?php

namespace App\Http\Controllers\Admin\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Genre;
use Illuminate\Http\Request;

class IssuesController extends Controller
{
    public function index()
    {
        $gamesWithoutRelease = Game::doesntHave('releases')
            ->orderBy('game_name')
            ->get();
        $gameWithoutGenre = Game::has('screenshots')
            ->doesntHave('genres')
            ->get()
            ->random();
        $gamesWithoutScreenshot = Game::doesntHave('screenshots')
            ->orderBy('game_name')
            ->get();

        $genres = Genre::orderBy('name')->get();

        return view('admin.games.issues.index')
            ->with([
                'gamesWithoutRelease'    => $gamesWithoutRelease,
                'gameWithoutGenre'       => $gameWithoutGenre,
                'gamesWithoutScreenshot' => $gamesWithoutScreenshot,
                'genres'                 => $genres,
            ]);
    }

    public function setGenres()
    {
        return redirect()->route('admin.games.issues');
    }
}
