<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChangeLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('change_log', function (Blueprint $table) {
            $table->integer('change_log_id', true);
            $table->string('section', 256);
            $table->integer('section_id');
            $table->string('section_name', 256);
            $table->string('sub_section', 256);
            $table->integer('sub_section_id');
            $table->string('sub_section_name', 256);
            $table->integer('user_id');
            $table->string('action', 256);
            $table->integer('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('change_log');
    }
}
