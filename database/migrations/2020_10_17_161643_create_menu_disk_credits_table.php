<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_credits', function (Blueprint $table) {
            $table->integer('menu_disk_credits_id', true);
            $table->integer('menu_disk_id')->nullable();
            $table->integer('ind_id')->nullable();
            $table->integer('author_type_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_credits');
    }
}
