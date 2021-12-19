<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndividualsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_individual', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_individual_ibfk_1');
                $table->dropForeign('game_individual_ibfk_2');
                $table->dropForeign('game_individual_ibfk_3');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('individual_id')
                ->references('ind_id')
                ->on('individuals')
                ->onDelete('cascade');

            $table->foreign('individual_role_id')
                ->references('id')
                ->on('individual_role')
                ->onDelete('cascade');
        });

        Schema::table('individual_nicks', function (Blueprint $table) {
            $table->foreign('ind_id')
                ->references('ind_id')
                ->on('individuals')
                ->onDelete('cascade');

            $table->foreign('nick_id')
                ->references('ind_id')
                ->on('individuals')
                ->onDelete('cascade');
        });

        // Some texts point to individuals that don't exist anymore
        // Delete them to be able to set the foreign key constraint
        DB::table('individual_text')
            ->whereRaw('ind_id not in (select ind_id from individuals)')
            ->delete();

        Schema::table('individual_text', function (Blueprint $table) {
            $table->foreign('ind_id')
                ->references('ind_id')
                ->on('individuals')
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
        Schema::table('game_individual', function (Blueprint $table) {
            $table->dropForeign('game_individual_game_id_foreign');
            $table->dropForeign('game_individual_individual_id_foreign');
            $table->dropForeign('game_individual_individual_role_id_foreign');

            $table->foreign('game_id', 'game_individual_ibfk_1')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('individual_id', 'game_individual_ibfk_2')
                ->references('ind_id')
                ->on('individuals')
                ->onDelete('cascade');

            $table->foreign('individual_role_id', 'game_individual_ibfk_3')
                ->references('id')
                ->on('individual_role')
                ->onDelete('cascade');
        });

        Schema::table('individual_nicks', function (Blueprint $table) {
            $table->dropForeign('individual_nicks_nick_id_foreign');
            $table->dropForeign('individual_nicks_ind_id_foreign');
        });

        Schema::table('individual_text', function (Blueprint $table) {
            $table->dropForeign('individual_text_ind_id_foreign');
        });
    }
}
