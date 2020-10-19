<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameAkaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_aka', function (Blueprint $table) {
            $table->foreign('language_id', 'game_aka_ibfk_1')->references('id')->on('language')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_aka', function (Blueprint $table) {
            $table->dropForeign('game_aka_ibfk_1');
        });
    }
}
