<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_location', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('location_id')->index()->comment('ID of a location the relase is from');
            $table->integer('game_release_id')->index()->comment('ID of the release');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_location');
    }
}
