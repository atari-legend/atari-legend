<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameIndividualTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_individual', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_individual');
            $table->integer('game_id')->nullable()->index()->comment('Foreign key to game table');
            $table->integer('individual_id')->nullable()->index()->comment('Foreign key to individual table');
            $table->integer('individual_role_id')->nullable()->index()->comment('Foreign key to individual role table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_individual');
    }
}
