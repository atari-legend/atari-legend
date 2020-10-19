<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsSearchWordmatchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_search_wordmatch', function (Blueprint $table) {
            $table->unsignedMediumInteger('news_id')->default(0)->index();
            $table->unsignedMediumInteger('news_word_id')->default(0)->index();
            $table->boolean('news_title_match')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_search_wordmatch');
    }
}
