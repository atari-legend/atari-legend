<?php

namespace App\Helpers;

use App\Models\Article;
use App\Models\Interview;
use App\Models\News;
use App\Models\Review;

class FeedHelper
{
    public function getFeedItems()
    {
        $news = News::orderByDesc('news_date')->limit(20)->get();

        $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED)
            ->orderByDesc('review_date')
            ->limit(20)
            ->get();

        $interviews = Interview::orderByDesc('interview_date')
            ->limit(20)
            ->get();

        $articles = Article::orderByDesc('article_date')
            ->limit(20)
            ->get();

        // Sort all items by descending date and only
        // retain the top 20 (of a mix of news, reviews, interviews and articles)
        return $news
            ->concat($reviews)
            ->concat($interviews)
            ->concat($articles)
            ->sortByDesc(function ($item) {
                return $item->toFeedItem()->updated;
            })
            ->take(20);
    }
}
