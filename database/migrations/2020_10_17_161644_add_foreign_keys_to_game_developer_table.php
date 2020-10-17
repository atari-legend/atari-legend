<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameDeveloperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_developer', function (Blueprint $table) {
            $table->foreign('developer_role_id', 'game_developer_ibfk_1')->references('id')->on('developer_role')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_developer', function (Blueprint $table) {
            $table->dropForeign('game_developer_ibfk_1');
        });
    }
}
