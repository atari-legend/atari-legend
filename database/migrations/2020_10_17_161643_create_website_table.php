<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website', function (Blueprint $table) {
            $table->integer('website_id', true);
            $table->string('website_name', 128)->nullable();
            $table->string('website_url')->nullable();
            $table->integer('website_date')->default(0);
            $table->integer('user_id')->nullable()->index();
            $table->string('website_imgext', 11)->nullable();
            $table->integer('website_count')->default(0);
            $table->integer('rate_number')->default(1);
            $table->integer('rate_score')->default(5);
            $table->string('user_ip', 32)->nullable();
            $table->boolean('inactive')->default(0);
            $table->text('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('website');
    }
}
