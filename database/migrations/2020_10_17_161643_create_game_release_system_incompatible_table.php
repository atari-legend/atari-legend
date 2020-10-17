<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseSystemIncompatibleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_system_incompatible', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('system_id')->index()->comment('ID of the system the release is incompatible with');
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
        Schema::dropIfExists('game_release_system_incompatible');
    }
}
