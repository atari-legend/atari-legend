<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleSetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_set', function (Blueprint $table) {
            $table->integer('menu_disk_title_set_id', true);
            $table->integer('menu_disk_title_set_nr');
            $table->integer('menu_disk_title_set_chain');
            $table->integer('menu_disk_title_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_set');
    }
}
