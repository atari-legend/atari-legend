<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game', function (Blueprint $table) {
            $table->integer('game_id', true);
            $table->string('game_name')->nullable()->comment('Main name of a game');
            $table->integer('game_series_id')->nullable()->index()->comment('ID of the series this game is part of');
            $table->integer('port_id')->nullable()->index()->comment('Foreign key to port table');
            $table->integer('progress_system_id')->nullable()->index()->comment('Foreign key to game_progress_system table');
            $table->integer('number_players_on_same_machine')->nullable()->comment('nr of players on the same machine');
            $table->integer('number_players_multiple_machines')->nullable()->comment('nr of players on multiple machine');
            $table->enum('multiplayer_type', ['Simultaneous', 'Turn by turn'])->nullable()->comment('What kind of multiplayer');
            $table->enum('multiplayer_hardware', ['Cartridge', 'Midi-Link'])->nullable()->comment('What kind of extra hw can be used');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game');
    }
}
