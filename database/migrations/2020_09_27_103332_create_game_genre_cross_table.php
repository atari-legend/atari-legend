<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameGenreCrossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_genre_cross', function (Blueprint $table) {
            $table->primary(['game_id', 'game_genre_id']);

            $table->integer('game_id');
            $table->foreign('game_id')->references('game_id')->on('game');

            $table->integer('game_genre_id');
            $table->foreign('game_genre_id')->references('id')->on('game_genre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_genre_cross');
    }
}
