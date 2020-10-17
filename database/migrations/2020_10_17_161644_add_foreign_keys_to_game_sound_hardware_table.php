<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameSoundHardwareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_sound_hardware', function (Blueprint $table) {
            $table->foreign('game_id', 'game_sound_hardware_ibfk_1')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('sound_hardware_id', 'game_sound_hardware_ibfk_2')->references('id')->on('sound_hardware')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_sound_hardware', function (Blueprint $table) {
            $table->dropForeign('game_sound_hardware_ibfk_1');
            $table->dropForeign('game_sound_hardware_ibfk_2');
        });
    }
}
