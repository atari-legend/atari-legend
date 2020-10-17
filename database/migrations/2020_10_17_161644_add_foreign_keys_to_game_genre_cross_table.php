<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameGenreCrossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_genre_cross', function (Blueprint $table) {
            $table->foreign('game_id', 'game_genre_cross_ibfk_1')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('game_genre_id', 'game_genre_cross_ibfk_2')->references('id')->on('game_genre')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_genre_cross', function (Blueprint $table) {
            $table->dropForeign('game_genre_cross_ibfk_1');
            $table->dropForeign('game_genre_cross_ibfk_2');
        });
    }
}
