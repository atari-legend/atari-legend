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
        // card_news renders the author of every item, so eager load them
        // rather than paying a lookup per news entry.
        $news = News::with('user')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $triviaQuote = TriviaQuote::inRandomOrder()->first();
        $triviaImages = $this->getTriviaImages();

        $spotlight = Spotlight::inRandomOrder()->first();

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
