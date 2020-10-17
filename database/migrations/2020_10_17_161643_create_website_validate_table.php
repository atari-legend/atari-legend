<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteValidateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_validate', function (Blueprint $table) {
            $table->integer('website_id', true);
            $table->string('website_name', 128)->nullable();
            $table->string('website_url')->nullable();
            $table->integer('website_date')->default(0);
            $table->integer('website_category')->default(0);
            $table->text('website_description')->nullable();
            $table->integer('user_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('website_validate');
    }
}
