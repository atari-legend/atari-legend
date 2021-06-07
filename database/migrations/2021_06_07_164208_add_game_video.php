<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGameVideo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_videos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('youtube_id', 16);
            $table->integer('game_id');

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
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
        Schema::drop('game_videos');
    }
}
