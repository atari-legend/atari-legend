<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemoSubmitinfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demo_submitinfo', function (Blueprint $table) {
            $table->integer('demo_submitinfo_id', true);
            $table->integer('demo_id')->default(0);
            $table->integer('user_id')->default(0)->index();
            $table->string('timestamp', 32)->nullable();
            $table->text('submit_text')->nullable();
            $table->char('demo_done', 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('demo_submitinfo');
    }
}
