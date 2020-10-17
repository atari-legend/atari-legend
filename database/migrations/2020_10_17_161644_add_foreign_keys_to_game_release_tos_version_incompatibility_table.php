<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToGameReleaseTosVersionIncompatibilityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_release_tos_version_incompatibility', function (Blueprint $table) {
            $table->foreign('release_id', 'game_release_tos_version_incompatibility_ibfk_1')->references('id')->on('game_release')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('tos_id', 'game_release_tos_version_incompatibility_ibfk_2')->references('id')->on('tos')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('language_id', 'game_release_tos_version_incompatibility_ibfk_3')->references('id')->on('language')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_release_tos_version_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_1');
            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_2');
            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_3');
        });
    }
}
