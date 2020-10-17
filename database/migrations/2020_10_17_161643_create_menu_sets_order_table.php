<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuSetsOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_sets_order', function (Blueprint $table) {
            $table->tinyInteger('menu_sets_id')->default(0);
            $table->string('menu_sets_start', 4)->nullable();
            $table->string('menu_sets_end', 4)->nullable();
            $table->string('menu_sets_order', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_sets_order');
    }
}
