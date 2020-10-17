<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMagazineGameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('magazine_game', function (Blueprint $table) {
            $table->integer('magazine_game_id', true);
            $table->integer('magazine_issue_id')->default(0);
            $table->integer('game_id')->default(0);
            $table->string('score', 11)->nullable();
            $table->integer('magazine_employe_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('magazine_game');
    }
}
