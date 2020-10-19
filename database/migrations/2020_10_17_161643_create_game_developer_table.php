<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameDeveloperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_developer', function (Blueprint $table) {
            $table->integer('dev_pub_id')->nullable()->index();
            $table->integer('game_id')->nullable()->index();
            $table->integer('developer_role_id')->nullable()->index()->comment('Role the developer had on the game');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_developer');
    }
}
