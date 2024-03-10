<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('game_fact', function (Blueprint $table) {
            $table->integer('game_id')
                ->nullable(false)
                ->change();

            $table->mediumText('game_fact')
                ->nullable(false)
                ->change();

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
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
        Schema::table('game_fact', function (Blueprint $table) {
            $table->dropForeign('game_fact_game_id_foreign');
        });
    }
};
