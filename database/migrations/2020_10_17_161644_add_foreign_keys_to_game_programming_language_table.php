<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameProgrammingLanguageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_programming_language', function (Blueprint $table) {
            $table->foreign('game_id', 'game_programming_language_ibfk_1')->references('game_id')->on('game')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('programming_language_id', 'game_programming_language_ibfk_2')->references('id')->on('programming_language')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_programming_language', function (Blueprint $table) {
            $table->dropForeign('game_programming_language_ibfk_1');
            $table->dropForeign('game_programming_language_ibfk_2');
        });
    }
}
