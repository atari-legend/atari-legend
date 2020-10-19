<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleToolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_tools', function (Blueprint $table) {
            $table->integer('menu_disk_title_tools_id', true);
            $table->integer('menu_disk_title_id')->nullable();
            $table->integer('tools_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_tools');
    }
}
