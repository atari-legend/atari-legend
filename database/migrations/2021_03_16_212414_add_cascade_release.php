<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCascadeRelease extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite does not support dropping foreign keys
            return;
        }

        Schema::table('game_release_emulator_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_emulator_incompatibility_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_emulator_incompatibility_ibfk_2');
            $table->foreign('emulator_id')->references('id')->on('emulator')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_release_emulator_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_emulator_incompatibility_emulator_id_foreign');
            $table->foreign('release_id', 'game_release_emulator_incompatibility_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_emulator_incompatibility_release_id_foreign');
            $table->foreign('emulator_id', 'game_release_emulator_incompatibility_ibfk_2')->references('id')->on('emulator');
        });
    }
}
