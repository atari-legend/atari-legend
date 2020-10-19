<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsSubmissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_submission', function (Blueprint $table) {
            $table->integer('news_submission_id', true);
            $table->string('news_headline', 128)->nullable();
            $table->text('news_text')->nullable();
            $table->integer('news_image_id')->default(0);
            $table->integer('user_id')->default(0);
            $table->integer('news_date')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_submission');
    }
}
