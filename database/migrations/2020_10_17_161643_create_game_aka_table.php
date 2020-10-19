<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameAkaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_aka', function (Blueprint $table) {
            $table->integer('game_aka_id', true);
            $table->integer('game_id')->default(0)->index();
            $table->string('aka_name', 128)->nullable();
            $table->char('language_id', 2)->nullable()->index()->comment('Foreign key to language table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_aka');
    }
}
