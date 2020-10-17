<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseScanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_scan', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID');
            $table->integer('game_release_id')->index()->comment('Foreign key to the game_release table');
            $table->enum('type', ['Box front', 'Box back', 'Goodie', 'Other'])->comment('Scan type');
            $table->enum('imgext', ['png', 'jpg'])->comment('Image file extension');
            $table->text('notes')->nullable()->comment('General notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_scan');
    }
}
