<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNewsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change news_image_id and user_id to make them nullable
        Schema::table('news', function (Blueprint $table) {
            $table->integer('news_image_id')->nullable()->default(null)->change();
            $table->integer('user_id')->nullable()->default(null)->change();
        });

        // Then delete rows that were using "0" to indicate there was no
        // news image. Should be NULL instead.
        DB::table('news')
            ->where('news_image_id', '=', 0)
            ->update(['news_image_id' => null]);

        // There's one "Empty" news item pointing to user 0, delete
        DB::table('news')
            ->where('user_id', '=', 0)
            ->delete();

        Schema::table('news', function (Blueprint $table) {
            $table->foreign('news_image_id')
                ->references('news_image_id')
                ->on('news_image')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('news', function (Blueprint $table) {
            $table->integer('news_image_id')->nullable(false)->default(0)->change();
            $table->integer('user_id')->nullable(false)->default(0)->change();

            $table->dropForeign('news_news_image_id_foreign');
            $table->dropForeign('news_user_id_foreign');
        });
    }
}
