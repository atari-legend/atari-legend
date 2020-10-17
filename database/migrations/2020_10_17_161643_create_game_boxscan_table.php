<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameBoxscanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_boxscan', function (Blueprint $table) {
            $table->integer('game_boxscan_id', true);
            $table->integer('game_id')->nullable()->index();
            $table->string('imgext', 11)->nullable();
            $table->char('game_boxscan_side', 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_boxscan');
    }
}
