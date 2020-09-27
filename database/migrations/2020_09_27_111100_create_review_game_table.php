<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_game', function (Blueprint $table) {
            $table->id('review_game_id');

            $table->integer('review_id');
            $table->foreign('review_id')->references('review_id')->on('review_main');

            $table->integer('game_id');
            $table->foreign('game_id')->references('game_id')->on('game');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_game');
    }
}
