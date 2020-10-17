<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseSystemEnhancedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_system_enhanced', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('system_id')->index()->comment('ID of the system the release is enhanced for');
            $table->integer('game_release_id')->index()->comment('ID of the release');
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
        Schema::dropIfExists('game_release_system_enhanced');
    }
}
