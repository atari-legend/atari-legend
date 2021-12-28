<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPubDevForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Some texts point to pub_dev that don't exist anymore
        // Delete them to be able to set the foreign key constraint
        DB::table('pub_dev_text')
            ->whereRaw('pub_dev_id not in (select pub_dev_id from pub_dev)')
            ->delete();

        Schema::table('pub_dev_text', function (Blueprint $table) {
            $table->foreign('pub_dev_id')
                ->references('pub_dev_id')
                ->on('pub_dev')
                ->onDelete('cascade');
        });

        // Some rows point to games that don't exist anymore
        DB::table('game_developer')
            ->whereRaw('game_id not in (select game_id from game)')
            ->delete();

        // Some rows point to pub_dev that don't exist anymore
        DB::table('game_developer')
            ->whereRaw('dev_pub_id not in (select pub_dev_id from pub_dev)')
            ->delete();

        Schema::table('game_developer', function (Blueprint $table) {
            $table->foreign('dev_pub_id')
                ->references('pub_dev_id')
                ->on('pub_dev')
                ->onDelete('cascade');

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_developer_ibfk_1');
            }

            $table->foreign('developer_role_id')
                ->references('id')
                ->on('developer_role')
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
        Schema::table('pub_dev_text', function (Blueprint $table) {
            $table->dropForeign('pub_dev_text_pub_dev_id_foreign');
        });

        Schema::table('game_developer', function (Blueprint $table) {
            $table->dropForeign('game_developer_dev_pub_id_foreign');
            $table->dropForeign('game_developer_game_id_foreign');
            $table->dropForeign('game_developer_developer_role_id_foreign');

            $table->foreign('developer_role_id', 'game_developer_ibfk_1')
                ->references('id')
                ->on('developer_role')
                ->onDelete('cascade');

        });
    }
}
