<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSoundHardwareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sound_hardware', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a sound hardware support');
            $table->string('name', 64)->comment('Hardware');
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
        Schema::dropIfExists('sound_hardware');
    }
}
