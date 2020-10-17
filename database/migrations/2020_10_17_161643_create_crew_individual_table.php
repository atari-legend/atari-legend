<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrewIndividualTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crew_individual', function (Blueprint $table) {
            $table->integer('crew_individual_id', true);
            $table->integer('crew_id')->nullable();
            $table->integer('ind_id')->nullable();
            $table->integer('individual_nicks_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crew_individual');
    }
}
