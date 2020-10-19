<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameReleaseCrewTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_release_crew', function (Blueprint $table) {
            $table->foreign('game_release_id', 'game_release_crew_ibfk_1')->references('id')->on('game_release')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('crew_id', 'game_release_crew_ibfk_2')->references('crew_id')->on('crew')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_release_crew', function (Blueprint $table) {
            $table->dropForeign('game_release_crew_ibfk_1');
            $table->dropForeign('game_release_crew_ibfk_2');
        });
    }
}
