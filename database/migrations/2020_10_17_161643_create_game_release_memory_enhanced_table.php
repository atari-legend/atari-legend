<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseMemoryEnhancedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_memory_enhanced', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_memory_enhanced record');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('memory_id')->index()->comment('Foreign key to the memory table');
            $table->integer('enhancement_id')->nullable()->index()->comment('Foreign key to enhancement table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_memory_enhanced');
    }
}
