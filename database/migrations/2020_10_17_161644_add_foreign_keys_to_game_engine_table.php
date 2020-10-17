<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameEngineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_engine', function (Blueprint $table) {
            $table->foreign('game_id', 'game_engine_ibfk_1')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('engine_id', 'game_engine_ibfk_2')->references('id')->on('engine')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_engine', function (Blueprint $table) {
            $table->dropForeign('game_engine_ibfk_1');
            $table->dropForeign('game_engine_ibfk_2');
        });
    }
}
