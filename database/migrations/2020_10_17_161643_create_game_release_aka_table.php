<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseAkaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_aka', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_aka');
            $table->integer('game_release_id')->index()->comment('foreign key to game_release table');
            $table->string('name', 256)->nullable()->comment('Name of AKA');
            $table->char('language_id', 2)->nullable()->index()->comment('foreign key to language table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_aka');
    }
}
