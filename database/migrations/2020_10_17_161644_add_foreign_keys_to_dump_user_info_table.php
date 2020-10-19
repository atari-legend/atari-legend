<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToDumpUserInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dump_user_info', function (Blueprint $table) {
            $table->foreign('dump_id', 'dump_user_info_ibfk_1')->references('id')->on('dump')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('user_id', 'dump_user_info_ibfk_2')->references('user_id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dump_user_info', function (Blueprint $table) {
            $table->dropForeign('dump_user_info_ibfk_1');
            $table->dropForeign('dump_user_info_ibfk_2');
        });
    }
}
