<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEngineTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('engine', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of an engine');
            $table->string('name', 64)->comment('name of engine');
            $table->string('description', 256)->nullable()->comment('Description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('engine');
    }
}
