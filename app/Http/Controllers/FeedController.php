<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Article;
use App\Models\Interview;
use App\Models\News;
use App\Models\Review;
use Illuminate\Support\Facades\App;

class FeedController extends Controller
{
    public function feed()
    {
        $feed = App::make('feed');

        $feed->setCache(60);

        if (!$feed->isCached()) {
            $feed->title = 'Atari Legend - Latest News, Reviews and Interviews';
            $feed->description = 'Legends Never Die!';
            $feed->icon = asset('images/favicon.png');
            $feed->link = route('feed');
            $feed->setDateFormat('timestamp');

            $feed->lang = 'en';

            // Collect latest items from various sections
            $items = [];

            $news = News::orderByDesc('news_date')->limit(20)->get();
            foreach ($news as $new) {
                $items[] = [
                    'title'  => $new->news_headline,
                    'author' => $new->user->userid,
                    // Use an ID so that articles in the feed have different IDs
                    // The ID is effectively ignored in the News page
                    'link'    => route('news.index', ['news' => $new->news_id]),
                    'updated' => $new->news_date,
                    'summary' => null,
                    'content' => Helper::bbCode(nl2br($new->news_text)),
                ];
            }

            $reviews = Review::where('review_edit', Review::REVIEW_PUBLISHED)
                ->orderByDesc('review_date')
                ->limit(20)
                ->get();
            foreach ($reviews as $review) {
                $items[] = [
                    'title'   => 'Review: '.$review->games->first()->game_name,
                    'author'  => $review->user->userid,
                    'link'    => route('reviews.show', ['review' => $review]),
                    'updated' => $review->review_date,
                    'summary' => Helper::bbCode(Helper::extractTag($review->review_text, 'frontpage')),
                    'content' => null,
                ];
            }

            $interviews = Interview::select()
                ->join('interview_text', 'interview_text.interview_id', '=', 'interview_main.interview_id')
                ->orderByDesc('interview_text.interview_date')
                ->limit(20)
                ->get();
            foreach ($interviews as $interview) {
                $items[] = [
                    'title'   => 'Interview: '.$interview->individual->ind_name,
                    'author'  => $interview->user->userid,
                    'link'    => route('interviews.show', ['interview' => $interview]),
                    'updated' => $interview->texts->first()->interview_date,
                    'summary' => Helper::bbCode($interview->texts->first()->interview_intro),
                    'content' => null,
                ];
            }

            $articles = Article::select()
                ->join('article_text', 'article_text.article_id', '=', 'article_main.article_id')
                ->orderByDesc('article_text.article_date')
                ->limit(20)
                ->get();
            foreach ($articles as $article) {
                $items[] = [
                    'title'   => 'Article: '.$article->article_title,
                    'author'  => $article->user->userid,
                    'link'    => route('articles.show', ['article' => $article]),
                    'updated' => $article->texts->first()->article_date,
                    'summary' => Helper::bbCode($article->texts->first()->article_intro),
                    'content' => null,
                ];
            }

            // Only retain the latest 20 items across all sections
            $items = collect($items)->sortByDesc('updated')->take(20)->all();
            foreach ($items as $item) {
                $feed->addItem([
                    'title'       => $item['title'],
                    'author'      => $item['author'],
                    'link'        => $item['link'],
                    'pubdate'     => date('c', $item['updated']),
                    'description' => $item['summary'],
                    'content'     => $item['content'],
                ]);
            }

            $feed->pubdate = reset($items)['updated'];
        }

        $feed->setCustomView('feed');

        return $feed->render('atom');
    }
}
