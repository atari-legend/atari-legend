<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewMainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_main', function (Blueprint $table) {
            $table->integer('review_id', true);
            $table->integer('user_id')->nullable()->index();
            $table->text('review_text')->nullable();
            $table->integer('review_date')->default(0);
            $table->integer('review_edit')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_main');
    }
}
