<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuStonishTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_stonish', function (Blueprint $table) {
            $table->integer('menu_stonish_id', true);
            $table->integer('sofware_type_id');
            $table->string('software_name');
            $table->integer('id_software');
            $table->integer('menu_disk_id');
            $table->integer('iddemozoo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_stonish');
    }
}
