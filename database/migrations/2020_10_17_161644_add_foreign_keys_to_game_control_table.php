<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameControlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_control', function (Blueprint $table) {
            $table->foreign('control_id', 'game_control_ibfk_1')->references('id')->on('control')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('game_id', 'game_control_ibfk_2')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_control', function (Blueprint $table) {
            $table->dropForeign('game_control_ibfk_1');
            $table->dropForeign('game_control_ibfk_2');
        });
    }
}
