<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgrammingLanguageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programming_language', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a programming language');
            $table->string('name', 64)->comment('programming_language');
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
        Schema::dropIfExists('programming_language');
    }
}
