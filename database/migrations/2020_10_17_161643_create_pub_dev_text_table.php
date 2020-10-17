<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePubDevTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pub_dev_text', function (Blueprint $table) {
            $table->integer('pub_dev_text', true);
            $table->integer('pub_dev_id')->nullable();
            $table->text('pub_dev_profile')->nullable();
            $table->string('pub_dev_imgext', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pub_dev_text');
    }
}
