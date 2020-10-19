<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameProgrammingLanguageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_programming_language', function (Blueprint $table) {
            $table->integer('game_id')->index()->comment('Foreign key to game table');
            $table->integer('programming_language_id')->index()->comment('Foreign key to programming_language table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_programming_language');
    }
}
