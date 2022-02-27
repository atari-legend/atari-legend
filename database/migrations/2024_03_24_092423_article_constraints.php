<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_text', function (Blueprint $table) {
            $table->integer('article_id')
                ->nullable(false)
                ->change();
            $table->mediumText('article_title')
                ->nullable(false)
                ->change();
            $table->mediumText('article_text')
                ->nullable(false)
                ->change();
            $table->mediumText('article_intro')
                ->nullable(false)
                ->change();
            $table->integer('article_date')
                ->nullable(false)
                ->change();

            $table->foreign('article_id')
                ->references('article_id')
                ->on('article_main')
                ->onDelete('cascade');
        });

        DB::table('screenshot_article')
            ->whereRaw('screenshot_id not in (select screenshot_id from screenshot_main)')
            ->delete();

        Schema::table('screenshot_article', function (Blueprint $table) {
            $table->integer('article_id')
                ->nullable(false)
                ->change();
            $table->integer('screenshot_id')
                ->nullable(false)
                ->change();

            $table->foreign('article_id')
                ->references('article_id')
                ->on('article_main')
                ->onDelete('cascade');
            $table->foreign('screenshot_id')
                ->references('screenshot_id')
                ->on('screenshot_main')
                ->onDelete('cascade');
        });

        Schema::table('article_comments', function (Blueprint $table) {
            $table->integer('screenshot_article_id')
                ->nullable(false)
                ->change();
            $table->mediumText('comment_text')
                ->nullable(false)
                ->change();

            $table->foreign('screenshot_article_id')
                ->references('screenshot_article_id')
                ->on('screenshot_article')
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
        Schema::table('article_text', function (Blueprint $table) {
            $table->integer('article_id')
                ->nullable(true)
                ->change();
            $table->mediumText('article_title')
                ->nullable(true)
                ->change();
            $table->mediumText('article_text')
                ->nullable(true)
                ->change();
            $table->mediumText('article_intro')
                ->nullable(true)
                ->change();
            $table->integer('article_date')
                ->nullable(true)
                ->change();

            $table->dropForeign('article_text_article_id_foreign');
        });

        Schema::table('screenshot_article', function (Blueprint $table) {
            $table->integer('article_id')
                ->nullable(true)
                ->change();
            $table->integer('screenshot_id')
                ->nullable(true)
                ->change();

            $table->dropForeign('screenshot_article_article_id_foreign');
            $table->dropForeign('screenshot_article_screenshot_id_foreign');
        });

        Schema::table('article_comments', function (Blueprint $table) {
            $table->integer('screenshot_article_id')
                ->nullable(true)
                ->change();
            $table->mediumText('comment_text')
                ->nullable(true)
                ->change();

            $table->dropForeign('article_comments_screenshot_article_id_foreign');
        });
    }
};
