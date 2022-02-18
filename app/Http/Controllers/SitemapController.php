<?php

namespace App\Http\Controllers;

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
        $interviews = Interview::orderBy('interview_id')->get();
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
        $games = Game::orderBy('game_name');

        if ($letter === '0-9') {
            $games->where('game_name', 'regexp', '^[0-9]+');
        } else {
            $games->where('game_name', 'like', $letter . '%');
        }

        return response()->view('sitemap.games', [
            'games' => $games->get(),
        ])
            ->withHeaders(['Content-Type' => 'text/xml']);
    }
}
