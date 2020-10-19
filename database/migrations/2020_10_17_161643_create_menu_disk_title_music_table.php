<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleMusicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_music', function (Blueprint $table) {
            $table->integer('menu_disk_title_music_id', true);
            $table->integer('menu_disk_title_id')->nullable();
            $table->integer('music_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_music');
    }
}
