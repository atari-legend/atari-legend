<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndMenuProdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ind_menu_prod', function (Blueprint $table) {
            $table->integer('ind_menu_prod_id', true);
            $table->integer('ind_id')->nullable();
            $table->integer('menu_sets_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ind_menu_prod');
    }
}
