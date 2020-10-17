<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->foreign('game_series_id', 'game_ibfk_1')->references('id')->on('game_series')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('port_id', 'game_ibfk_2')->references('id')->on('port')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('progress_system_id', 'game_ibfk_3')->references('id')->on('game_progress_system')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->dropForeign('game_ibfk_1');
            $table->dropForeign('game_ibfk_2');
            $table->dropForeign('game_ibfk_3');
        });
    }
}
