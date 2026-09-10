<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Category;
use App\Models\Game;
use App\Models\Interview;
use App\Models\Review;

class SitemapController extends Controller
{
    public function index()
    {
        return response()->view('sitemap.index')
            ->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function general()
    {
        $interviews = Interview::orderBy('id')->get();
        $reviews = Review::where('submission', Review::REVIEW_PUBLISHED)
            ->orderBy('published_at')
            ->get();
        $categories = Category::orderBy('name')->get();

        return response()->view('sitemap.general', [
            'interviews' => $interviews,
            'reviews'    => $reviews,
            'categories' => $categories,
        ])
            ->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function games($letter)
    {
        $games = Helper::whereTitleStartsWith(
            Game::orderBy('name'),
            'name',
            $letter
        );

        return response()->view('sitemap.games', [
            'games' => $games->get(),
        ])
            ->withHeaders(['Content-Type' => 'text/xml']);
    }
}
