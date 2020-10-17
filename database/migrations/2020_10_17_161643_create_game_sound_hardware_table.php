<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameSoundHardwareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_sound_hardware', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_sound_hardware');
            $table->integer('game_id')->index()->comment('Foreign key to game table');
            $table->integer('sound_hardware_id')->index()->comment('Foreign key to sound_hardware table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_sound_hardware');
    }
}
