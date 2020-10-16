<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Spotlight;
use App\Models\TriviaQuote;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        $news = News::all()
            ->sortByDesc('news_date')
            ->take(6);

        $triviaQuote = TriviaQuote::all()
            ->random();

        $triviaImages = $this->getTriviaImages();

        $spotlight = Spotlight::all()
            ->random();

        return view('home.index')->with([
            'news'         => $news,
            'triviaQuote'  => $triviaQuote,
            'triviaImages' => $triviaImages,
            'spotlight'    => $spotlight,
        ]);
    }

    private function getTriviaImages()
    {
        return collect(Storage::disk('images')->files('cards/trivia/'));
    }
}
