<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoteTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_votes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('game_id');
            $table->integer('user_id');
            $table->integer('score');

            $table->unique(['game_id', 'user_id']);

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('game_votes');
    }
}
