<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameReleaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_release', function (Blueprint $table) {
            $table->foreign('pub_dev_id', 'game_release_ibfk_3')->references('pub_dev_id')->on('pub_dev')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_release', function (Blueprint $table) {
            $table->dropForeign('game_release_ibfk_3');
        });
    }
}
