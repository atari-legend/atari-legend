<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();

            $table->integer('game_id');
            $table->foreign('game_id')->references('game_id')->on('game');

            $table->integer('pub_dev_id')->nullable();
            $table->foreign('pub_dev_id')->references('pub_dev_id')->on('pub_dev');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release');
    }
}
