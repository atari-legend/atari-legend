<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseTosVersionIncompatibilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release_tos_version_incompatibility', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a game_release_tos_version_incompatibility');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('tos_id')->index()->comment('Foreign key to TOS version table');
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
        Schema::dropIfExists('game_release_tos_version_incompatibility');
    }
}
