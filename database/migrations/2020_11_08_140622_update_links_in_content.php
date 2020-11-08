<?php

use App\Models\ArticleText;
use App\Models\GameFact;
use App\Models\InterviewText;
use App\Models\News;
use App\Models\Review;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLinksInContent extends Migration
{

    const REPLACEMENTS = [
        [
            'sql'         => '\[url=[^]]*/interviews/interviews_detail\.php\?selected_interview_id=',
            'regexp'      => "@\[url=.*?\/interviews\/interviews_detail\.php\?selected_interview_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[interview=$1]$2[/interview]',
        ],
        [
            'sql'         => '\[url=[^]]*/games/games_detail\.php\?game_id=',
            'regexp'      => "@\[url=.*?\/games\/games_detail\.php\?game_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[game=$1]$2[/game]',
        ],
        [
            'sql'         => '\[url=[^]]*/games/games_reviews_detail\.php\?review_id=',
            'regexp'      => "@\[url=.*?\/games\/games_reviews_detail\.php\?review_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[review=$1]$2[/review]',
        ],
        [
            'sql'         => '\[url=[^]]*/articles/articles_detail\.php\?selected_article_id=',
            'regexp'      => "@\[url=.*?\/articles\/articles_detail\.php\?selected_article_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[article=$1]$2[/article]',
        ],
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        collect(UpdateLinksInContent::REPLACEMENTS)->each(function ($replacement) {
            News::where('news_text', 'regexp', $replacement['sql'])
                ->each(function ($news) use ($replacement) {
                    $text = preg_replace(
                        $replacement['regexp'],
                        $replacement['replacement'],
                        $news->news_text
                    );
                    $news->news_text = $text;
                    $news->save();
                });

            Review::where('review_text', 'regexp', $replacement['sql'])
                ->each(function ($review) use ($replacement) {
                    $text = preg_replace(
                        $replacement['regexp'],
                        $replacement['replacement'],
                        $review->review_text
                    );
                    $review->review_text = $text;
                    $review->save();
                });

            InterviewText::where('interview_text', 'regexp', $replacement['sql'])
                ->each(function ($interview) use ($replacement) {
                    $text = preg_replace(
                        $replacement['regexp'],
                        $replacement['replacement'],
                        $interview->interview_text
                    );
                    $interview->interview_text = $text;
                    $interview->save();
                });

            InterviewText::where('interview_intro', 'regexp', $replacement['sql'])
                ->each(function ($interview) use ($replacement) {
                    $text = preg_replace(
                        $replacement['regexp'],
                        $replacement['replacement'],
                        $interview->interview_intro
                    );
                    $interview->interview_intro = $text;
                    $interview->save();
                });

            ArticleText::where('article_text', 'regexp', $replacement['sql'])
                ->each(function ($article) use ($replacement) {
                    $text = preg_replace(
                        $replacement['regexp'],
                        $replacement['replacement'],
                        $article->article_text
                    );
                    $article->article_text = $text;
                    $article->save();
                });

            GameFact::where('game_fact', 'regexp', $replacement['sql'])
            ->each(function ($fact) use ($replacement) {
                $text = preg_replace(
                    $replacement['regexp'],
                    $replacement['replacement'],
                    $fact->game_fact
                );
                $fact->game_fact = $text;
                $fact->save();
            });
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content', function (Blueprint $table) {
            //
        });
    }
}
