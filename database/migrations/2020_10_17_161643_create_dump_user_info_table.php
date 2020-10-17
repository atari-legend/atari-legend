<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDumpUserInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dump_user_info', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a dump');
            $table->integer('dump_id')->index()->comment('Foreign key to dump table');
            $table->integer('user_id')->index()->comment('Foreign key to user table');
            $table->date('date')->nullable()->comment('Date when dump was downloaded');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dump_user_info');
    }
}
