<?php

namespace App\Http\Controllers;

use App\Helpers\StatisticsHelper;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index() {

        $news = \App\News::all()
            ->sortByDesc("news_date")
            ->take(6);

        $triviaQuote = \App\TriviaQuote::all()
            ->random();

        $triviaImages = $this->getTriviaImages();

        $spotlight = \App\Spotlight::all()
            ->random();

        $stats = StatisticsHelper::getStatistics();

        return view('home.index')->with([
            "news" => $news,
            "triviaQuote" => $triviaQuote,
            "triviaImages" => $triviaImages,
            "spotlight" => $spotlight,
            "stats" => $stats,
        ]);
    }

    private function getTriviaImages()
    {
        return collect(Storage::disk('images')->files('cards/trivia/'));
    }

}
