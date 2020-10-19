<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameBoxCouplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_box_couples', function (Blueprint $table) {
            $table->integer('game_box_couples_id', true);
            $table->integer('game_boxscan_id')->nullable();
            $table->integer('game_boxscan_cross')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_box_couples');
    }
}
