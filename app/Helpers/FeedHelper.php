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

        $interviews = Interview::select()
            ->join('interview_text', 'interview_text.interview_id', '=', 'interview_main.interview_id')
            ->orderByDesc('interview_text.interview_date')
            ->limit(20)
            ->get();

        $articles = Article::select()
            ->join('article_text', 'article_text.article_id', '=', 'article_main.article_id')
            ->orderByDesc('article_text.article_date')
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
