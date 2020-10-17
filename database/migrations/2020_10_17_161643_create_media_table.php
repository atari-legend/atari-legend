<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a media');
            $table->integer('release_id')->index()->comment('Foreign key to release table');
            $table->integer('media_type_id')->index()->comment('Foreign key to the media_type table');
            $table->string('label', 256)->nullable()->comment('Media label (e.g. \'Disk A\')');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media');
    }
}
