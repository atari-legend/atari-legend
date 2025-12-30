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
        Schema::table('review_main', function (Blueprint $table) {
            $table->mediumText('review_text')
                ->nullable(false)
                ->change();
            $table->integer('review_date')
                ->nullable(false)
                ->change();
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('review_game', function (Blueprint $table) {
            $table->integer('review_id')
                ->default(null)
                ->change();
            $table->integer('game_id')
                ->default(null)
                ->change();

            $table->foreign('review_id')
                ->references('review_id')
                ->on('review_main')
                ->onDelete('cascade');
            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');
        });

        Schema::table('review_score', function (Blueprint $table) {
            $table->integer('review_id')
                ->default(null)
                ->change();
            $table->integer('review_graphics')
                ->nullable(false)
                ->change();
            $table->integer('review_sound')
                ->nullable(false)
                ->change();
            $table->integer('review_gameplay')
                ->nullable(false)
                ->change();
            $table->integer('review_overall')
                ->nullable(false)
                ->change();

            $table->foreign('review_id')
                ->references('review_id')
                ->on('review_main')
                ->onDelete('cascade');
        });

        DB::table('screenshot_review')
            ->whereRaw('screenshot_id not in (select screenshot_id from screenshot_main)')
            ->delete();

        Schema::table('screenshot_review', function (Blueprint $table) {
            $table->integer('review_id')
                ->nullable(false)
                ->change();
            $table->integer('screenshot_id')
                ->nullable(false)
                ->change();

            $table->foreign('review_id')
                ->references('review_id')
                ->on('review_main')
                ->onDelete('cascade');
            $table->foreign('screenshot_id')
                ->references('screenshot_id')
                ->on('screenshot_main')
                ->onDelete('cascade');
        });

        DB::table('review_comments')
            ->whereRaw('screenshot_review_id not in (select screenshot_review_id from screenshot_review)')
            ->delete();

        Schema::table('review_comments', function (Blueprint $table) {
            $table->integer('screenshot_review_id')
                ->default(null)
                ->change();
            $table->mediumText('comment_text')
                ->nullable(false)
                ->change();

            $table->foreign('screenshot_review_id')
                ->references('screenshot_review_id')
                ->on('screenshot_review')
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
        Schema::table('review_main', function (Blueprint $table) {
            $table->mediumText('review_text')
                ->nullable(true)
                ->change();
            $table->integer('review_date')
                ->nullable(true)
                ->change();
            $table->dropForeign('review_main_user_id_foreign');
        });

        Schema::table('review_game', function (Blueprint $table) {
            $table->integer('review_id')
                ->default(0)
                ->change();
            $table->integer('game_id')
                ->default(0)
                ->change();

            $table->dropForeign('review_game_review_id_foreign');
            $table->dropForeign('review_game_game_id_foreign');
        });

        Schema::table('review_score', function (Blueprint $table) {
            $table->integer('review_id')
                ->default(0)
                ->change();
            $table->char('review_graphics', 2)
                ->nullable(true)
                ->change();
            $table->char('review_sound', 2)
                ->nullable(true)
                ->change();
            $table->char('review_gameplay', 2)
                ->nullable(true)
                ->change();
            $table->char('review_overall', 2)
                ->nullable(true)
                ->change();

            $table->dropForeign('review_score_review_id_foreign');
        });

        Schema::table('screenshot_review', function (Blueprint $table) {
            $table->integer('review_id')
                ->nullable(true)
                ->change();
            $table->integer('screenshot_id')
                ->nullable(true)
                ->change();

            $table->dropForeign('screenshot_review_review_id_foreign');
            $table->dropForeign('screenshot_review_screenshot_id_foreign');
        });

        Schema::table('review_comments', function (Blueprint $table) {
            $table->integer('screenshot_review_id')
                ->default(0)
                ->change();
            $table->mediumText('comment_text')
                ->nullable(true)
                ->change();

            $table->dropForeign('review_comments_screenshot_review_id_foreign');
        });
    }
};
