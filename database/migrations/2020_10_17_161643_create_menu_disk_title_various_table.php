<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleVariousTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_various', function (Blueprint $table) {
            $table->tinyInteger('menu_disk_title_various_id', true);
            $table->tinyInteger('menu_disk_title_id')->nullable();
            $table->string('menu_disk_title_various', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_various');
    }
}
