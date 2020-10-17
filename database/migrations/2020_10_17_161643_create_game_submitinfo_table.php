<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameSubmitinfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_submitinfo', function (Blueprint $table) {
            $table->integer('game_submitinfo_id', true);
            $table->integer('game_id')->default(0);
            $table->integer('user_id')->default(0)->index();
            $table->string('timestamp', 32)->nullable();
            $table->text('submit_text')->nullable();
            $table->char('game_done', 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_submitinfo');
    }
}
