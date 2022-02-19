<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArticleForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('article_main', function (Blueprint $table) {
            $table->foreign('article_type_id')
                ->references('article_type_id')
                ->on('article_type')
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
        Schema::table('article_main', function (Blueprint $table) {
            $table->dropForeign('article_main_article_type_id_foreign');
            $table->dropForeign('article_main_user_id_foreign');
        });
    }
}
