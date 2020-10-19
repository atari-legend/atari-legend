<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseDiskProtectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_disk_protection', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_disk_protection');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('disk_protection_id')->index()->comment('Foreign key to disk_protection table');
            $table->text('notes')->nullable()->comment('Add a note to the protection type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release_disk_protection');
    }
}
