<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDumpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dump', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a dump');
            $table->integer('media_id')->index()->comment('Foreign key to media table');
            $table->enum('format', ['STX', 'MSA', 'RAW', 'SCP', 'ST'])->nullable()->comment('Dump binary file format');
            $table->string('sha512', 128)->nullable()->comment('SHA512 hash of the dump');
            $table->integer('date')->nullable()->comment('Upload date of the dump');
            $table->integer('size')->nullable()->comment('Size of the dump');
            $table->text('info')->nullable()->comment('Extra info for the dump');
            $table->integer('user_id')->index()->comment('Foreign key to users table to the user who ho added the dump');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dump');
    }
}
