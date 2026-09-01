<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateLinksInContent extends Migration
{
    const REPLACEMENTS = [
        [
            'sql'         => '\[url=[^]]*/interviews/interviews_detail\.php\?selected_interview_id=',
            'regexp'      => "@\[url=[^]]+?\/interviews\/interviews_detail\.php\?selected_interview_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[interview=$1]$2[/interview]',
        ],
        [
            'sql'         => '\[url=[^]]*/games/games_detail\.php\?game_id=',
            'regexp'      => "@\[url=[^]]+?\/games\/games_detail\.php\?game_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[game=$1]$2[/game]',
        ],
        [
            'sql'         => '\[url=[^]]*/games/games_reviews_detail\.php\?review_id=',
            'regexp'      => "@\[url=[^]]+?\/games\/games_reviews_detail\.php\?review_id=([0-9]+)](.*?)\[\/url\]@i",
            'replacement' => '[review=$1]$2[/review]',
        ],
        [
            'sql'         => '\[url=[^]]*/articles/articles_detail\.php\?selected_article_id=',
            'regexp'      => "@\[url=[^]]+?\/articles\/articles_detail\.php\?selected_article_id=([0-9]+)](.*?)\[\/url\]@i",
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
        // Skipped on SQLite, which does not support regexp by default, and
        // this migration is unimportant for unit tests
        if (DB::connection()->getDriverName() !== 'sqlite') {
            collect(UpdateLinksInContent::REPLACEMENTS)->each(function ($replacement) {
                DB::table('news')->where('news_text', 'regexp', $replacement['sql'])
                    ->orderBy('news_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $news) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $news->text
                            );
                            DB::table('news')->where('news_id', $news->news_id)->update(['news_text' => $text]);
                        }
                    });

                DB::table('review_main')->where('review_text', 'regexp', $replacement['sql'])
                    ->orderBy('review_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $review) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $review->review_text
                            );
                            DB::table('review_main')->where('review_id', $review->review_id)->update(['review_text' => $text]);
                        }
                    });

                DB::table('interview_text')->where('interview_text', 'regexp', $replacement['sql'])
                    ->orderBy('interview_text_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $interview) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $interview->interview_text
                            );
                            DB::table('interview_text')->where('interview_text_id', $interview->interview_text_id)->update(['interview_text' => $text]);
                        }
                    });

                DB::table('interview_text')->where('interview_intro', 'regexp', $replacement['sql'])
                    ->orderBy('interview_text_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $interview) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $interview->interview_intro
                            );
                            DB::table('interview_text')->where('interview_text_id', $interview->interview_text_id)->update(['interview_intro' => $text]);
                        }
                    });

                DB::table('article_text')->where('article_text', 'regexp', $replacement['sql'])
                    ->orderBy('article_text_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $article) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $article->text
                            );
                            DB::table('article_text')->where('article_text_id', $article->article_text_id)->update(['article_text' => $text]);
                        }
                    });

                DB::table('game_fact')->where('game_fact', 'regexp', $replacement['sql'])
                    ->orderBy('game_fact_id')->chunk(100, function ($rows) use ($replacement) {
                        foreach ($rows as $fact) {
                            $text = preg_replace(
                                $replacement['regexp'],
                                $replacement['replacement'],
                                $fact->game_fact
                            );
                            DB::table('game_fact')->where('game_fact_id', $fact->game_fact_id)->update(['game_fact' => $text]);
                        }
                    });
            });
        }
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
