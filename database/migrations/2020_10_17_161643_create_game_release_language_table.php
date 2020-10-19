<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseLanguageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_language', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_copy_protection');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
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
        Schema::dropIfExists('game_release_language');
    }
}
