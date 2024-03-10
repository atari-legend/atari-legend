<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screenshot_game_fact', function (Blueprint $table) {
            $table->integer('game_fact_id')
                ->nullable(false)
                ->change();

            $table->integer('screenshot_id')
                ->nullable(false)
                ->change();

            DB::table('screenshot_game_fact')
                ->whereRaw('game_fact_id not in (select game_fact_id from game_fact)')
                ->delete();

            $table->foreign('game_fact_id')
                ->references('game_fact_id')
                ->on('game_fact')
                ->onDelete('cascade');

            DB::table('screenshot_game_fact')
                ->whereRaw('screenshot_id not in (select screenshot_id from screenshot_main)')
                ->delete();

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
        Schema::table('screenshot_game_fact', function (Blueprint $table) {
            $table->integer('game_fact_id')
                ->nullable(false)
                ->change();

            $table->integer('screenshot_id')
                ->nullable(false)
                ->change();

            $table->dropForeign('screenshot_game_fact_game_fact_id_foreign');
            $table->dropForeign('screenshot_game_fact_screenshot_id_foreign');
        });
    }
};
