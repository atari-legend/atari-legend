<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameGenreCrossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_genre_cross', function (Blueprint $table) {
            $table->integer('game_id')->nullable()->index()->comment('Foreign key to game table');
            $table->integer('game_genre_id')->nullable()->index()->comment('Foreign key to game_genre table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_genre_cross');
    }
}
