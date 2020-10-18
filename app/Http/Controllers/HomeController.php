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

        $triviaQuote = null;
        TriviaQuote::all()
            ->whenNotEmpty(function ($collection) use (&$triviaQuote) {
                $triviaQuote = $collection->random();
            });

        $triviaImages = $this->getTriviaImages();

        $spotlight = null;
        Spotlight::all()
            ->whenNotEmpty(function ($collection) use (&$spotlight) {
                $spotlight = $collection->random();
            });

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
