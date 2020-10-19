<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameIndividualTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_individual', function (Blueprint $table) {
            $table->foreign('game_id', 'game_individual_ibfk_1')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('individual_id', 'game_individual_ibfk_2')->references('ind_id')->on('individuals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('individual_role_id', 'game_individual_ibfk_3')->references('id')->on('individual_role')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_individual', function (Blueprint $table) {
            $table->dropForeign('game_individual_ibfk_1');
            $table->dropForeign('game_individual_ibfk_2');
            $table->dropForeign('game_individual_ibfk_3');
        });
    }
}
