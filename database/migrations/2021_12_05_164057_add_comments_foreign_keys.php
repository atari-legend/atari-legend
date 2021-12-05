<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommentsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_user_comments', function (Blueprint $table) {
            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('comment_id')
                ->references('comments_id')
                ->on('comments')
                ->onDelete('cascade');
        });

        Schema::table('article_user_comments', function (Blueprint $table) {
            $table->foreign('article_id')
                ->references('article_id')
                ->on('article_main')
                ->onDelete('cascade');

            $table->foreign('comments_id')
                ->references('comments_id')
                ->on('comments')
                ->onDelete('cascade');
        });

        Schema::table('interview_user_comments', function (Blueprint $table) {
            $table->foreign('interview_id')
                ->references('interview_id')
                ->on('interview_main')
                ->onDelete('cascade');

            $table->foreign('comment_id')
                ->references('comments_id')
                ->on('comments')
                ->onDelete('cascade');
        });

        Schema::table('review_user_comments', function (Blueprint $table) {
            $table->foreign('review_id')
                ->references('review_id')
                ->on('review_main')
                ->onDelete('cascade');

            $table->foreign('comment_id')
                ->references('comments_id')
                ->on('comments')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_user_comments', function (Blueprint $table) {
            $table->dropForeign('game_user_comments_game_id_foreign');
            $table->dropForeign('game_user_comments_comment_id_foreign');
        });

        Schema::table('article_user_comments', function (Blueprint $table) {
            $table->dropForeign('article_user_comments_article_id_foreign');
            $table->dropForeign('article_user_comments_comments_id_foreign');
        });

        Schema::table('interview_user_comments', function (Blueprint $table) {
            $table->dropForeign('interview_user_comments_interview_id_foreign');
            $table->dropForeign('interview_user_comments_comment_id_foreign');
        });

        Schema::table('review_user_comments', function (Blueprint $table) {
            $table->dropForeign('review_user_comments_review_id_foreign');
            $table->dropForeign('review_user_comments_comment_id_foreign');
        });
    }
}
