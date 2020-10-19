<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToMediaScanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media_scan', function (Blueprint $table) {
            $table->foreign('media_id', 'media_scan_ibfk_1')->references('id')->on('media')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('media_scan_type_id', 'media_scan_ibfk_2')->references('id')->on('media_scan_type')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media_scan', function (Blueprint $table) {
            $table->dropForeign('media_scan_ibfk_1');
            $table->dropForeign('media_scan_ibfk_2');
        });
    }
}
