<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScreenshotGameSubmitinfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screenshot_game_submitinfo', function (Blueprint $table) {
            $table->integer('screenshot_game_submitinfo_id', true);
            $table->integer('game_submitinfo_id')->nullable();
            $table->integer('screenshot_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screenshot_game_submitinfo');
    }
}
