<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndividualTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individual_text', function (Blueprint $table) {
            $table->integer('ind_text_id', true);
            $table->integer('ind_id')->nullable();
            $table->text('ind_profile')->nullable();
            $table->string('ind_imgext', 50)->nullable();
            $table->string('ind_email', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('individual_text');
    }
}
