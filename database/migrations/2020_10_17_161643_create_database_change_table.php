<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatabaseChangeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('database_change', function (Blueprint $table) {
            $table->integer('database_change_id', true);
            $table->integer('database_update_id');
            $table->text('update_description');
            $table->integer('execute_timestamp');
            $table->string('implementation_state', 256);
            $table->string('update_filename', 256);
            $table->text('database_change_script');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('database_change');
    }
}
