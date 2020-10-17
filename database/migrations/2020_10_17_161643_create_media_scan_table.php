<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaScanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_scan', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a media_scan');
            $table->integer('media_id')->index()->comment('Foreign key to media table');
            $table->integer('media_scan_type_id')->index()->comment('Foreign key to the media_scan_type table');
            $table->string('imgext', 11)->nullable()->comment('Extension of the media image file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_scan');
    }
}
