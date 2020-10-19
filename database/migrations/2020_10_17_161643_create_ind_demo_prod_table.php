<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndDemoProdTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ind_demo_prod', function (Blueprint $table) {
            $table->tinyInteger('ind_demo_prod_id', true);
            $table->tinyInteger('ind_id')->nullable();
            $table->tinyInteger('demo_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ind_demo_prod');
    }
}
