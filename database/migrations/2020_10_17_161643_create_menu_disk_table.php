<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk', function (Blueprint $table) {
            $table->integer('menu_disk_id', true);
            $table->integer('menu_sets_id')->nullable();
            $table->integer('menu_disk_number')->nullable();
            $table->char('menu_disk_letter', 1)->nullable();
            $table->char('menu_disk_part', 1)->nullable();
            $table->char('menu_disk_version', 1)->nullable();
            $table->integer('state')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk');
    }
}
