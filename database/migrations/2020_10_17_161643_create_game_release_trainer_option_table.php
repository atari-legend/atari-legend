<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseTrainerOptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_trainer_option', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_trainer_options record');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('trainer_option_id')->index()->comment('Foreign key to trainer_options table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_trainer_option');
    }
}
