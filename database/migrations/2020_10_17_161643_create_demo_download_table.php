<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemoDownloadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demo_download', function (Blueprint $table) {
            $table->integer('demo_download_id', true);
            $table->integer('demo_id')->default(0)->index();
            $table->integer('set_nr')->default(0);
            $table->char('demo_ext', 3)->nullable();
            $table->integer('date')->default(0);
            $table->string('md5', 60)->nullable();
            $table->string('cracker', 60)->nullable();
            $table->string('supplier', 60)->nullable();
            $table->string('screen', 11)->nullable();
            $table->string('language', 11)->nullable();
            $table->string('trainer', 11)->nullable();
            $table->integer('disks')->nullable();
            $table->integer('legend')->nullable();
            $table->integer('harddrive')->default(0);
            $table->string('intro', 128)->nullable();
            $table->char('disable', 1)->nullable();
            $table->string('version', 5)->nullable();
            $table->string('tos', 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demo_download');
    }
}
