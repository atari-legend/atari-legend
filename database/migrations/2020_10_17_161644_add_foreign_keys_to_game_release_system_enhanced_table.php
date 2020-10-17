<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameReleaseSystemEnhancedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_release_system_enhanced', function (Blueprint $table) {
            $table->foreign('system_id', 'game_release_system_enhanced_ibfk_1')->references('id')->on('system')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('game_release_id', 'game_release_system_enhanced_ibfk_2')->references('id')->on('game_release')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('enhancement_id', 'game_release_system_enhanced_ibfk_3')->references('id')->on('enhancement')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_release_system_enhanced', function (Blueprint $table) {
            $table->dropForeign('game_release_system_enhanced_ibfk_1');
            $table->dropForeign('game_release_system_enhanced_ibfk_2');
            $table->dropForeign('game_release_system_enhanced_ibfk_3');
        });
    }
}
