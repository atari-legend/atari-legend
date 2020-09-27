<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScreenshotGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screenshot_game', function (Blueprint $table) {
            $table->id('screenshot_game_id');

            $table->integer('game_id');
            $table->foreign('game_id')->references('game_id')->on('game');

            $table->integer('screenshot_id');
            $table->foreign('screenshot_id')->references('screenshot_id')->on('screenshot_main');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screenshot_game');
    }
}
