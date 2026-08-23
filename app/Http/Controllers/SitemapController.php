<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Game;
use App\Models\Interview;
use App\Models\Review;
use App\Models\WebsiteCategory;

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
        $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED)
            ->orderBy('review_date')
            ->get();
        $categories = WebsiteCategory::orderBy('website_category_name')->get();

        return response()->view('sitemap.general', [
            'interviews'        => $interviews,
            'reviews'           => $reviews,
            'websiteCategories' => $categories,
        ])
            ->withHeaders(['Content-Type' => 'text/xml']);
    }

    public function games($letter)
    {
        $games = Helper::whereTitleStartsWith(
            Game::orderBy('game_name'),
            'game_name',
            $letter
        );

        return response()->view('sitemap.games', [
            'games' => $games->get(),
        ])
            ->withHeaders(['Content-Type' => 'text/xml']);
    }
}
