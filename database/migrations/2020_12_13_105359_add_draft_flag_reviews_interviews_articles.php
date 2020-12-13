<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDraftFlagReviewsInterviewsArticles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_main', function (Blueprint $table) {
            $table->boolean('draft')
                ->default(false)
                ->comment('Whether the article is an unpublished draft');
        });
        Schema::table('review_main', function (Blueprint $table) {
            $table->boolean('draft')
                ->default(false)
                ->comment('Whether the review is an unpublished draft');
        });
        Schema::table('interview_main', function (Blueprint $table) {
            $table->boolean('draft')
                ->default(false)
                ->comment('Whether the interview is an unpublished draft');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('article_main', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
        Schema::table('review_main', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
        Schema::table('interview_main', function (Blueprint $table) {
            $table->dropColumn('draft');
        });
    }
}
