<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseEmulatorIncompatibilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_emulator_incompatibility', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_emulator_incompatibility');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('emulator_id')->index()->comment('Foreign key to emulator table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_emulator_incompatibility');
    }
}
