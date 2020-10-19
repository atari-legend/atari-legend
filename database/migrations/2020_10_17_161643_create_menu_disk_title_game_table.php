<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_game', function (Blueprint $table) {
            $table->integer('menu_disk_title_game_id', true);
            $table->integer('menu_disk_title_id')->nullable();
            $table->integer('game_id')->nullable();
            $table->boolean('demo_version')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_game');
    }
}
