<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddGameSubmitinfoForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Some submissions point to games that don't exist anymore
        // Delete them to be able to set the foreign key constraint
        DB::table('game_submitinfo')
            ->whereRaw('game_id not in (select game_id from game)')
            ->delete();

        Schema::table('game_submitinfo', function (Blueprint $table) {
            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users');
        });

        Schema::table('screenshot_game_submitinfo', function (Blueprint $table) {
            $table->foreign('game_submitinfo_id')
                ->references('game_submitinfo_id')
                ->on('game_submitinfo')
                ->onDelete('cascade');

            $table->foreign('screenshot_id')
                ->references('screenshot_id')
                ->on('screenshot_main')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_submitinfo', function (Blueprint $table) {
            $table->dropForeign('game_submitinfo_game_id_foreign');
            $table->dropForeign('game_submitinfo_user_id_foreign');
        });

        Schema::table('screenshot_game_submitinfo', function (Blueprint $table) {
            $table->dropForeign('screenshot_game_submitinfo_game_submitinfo_id_foreign');
            $table->dropForeign('screenshot_game_submitinfo_screenshot_id_foreign');
        });
    }
}
