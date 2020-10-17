<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseDistributorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_distributor', function (Blueprint $table) {
            $table->integer('game_release_id')->index()->comment('Foreign key to game_release table');
            $table->integer('pub_dev_id')->index()->comment('Foreign key to pub_dev table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_distributor');
    }
}
