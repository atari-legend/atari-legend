<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('review_score', function (Blueprint $table) {
            $table->integer('review_score_id', true);
            $table->integer('review_id')->default(0);
            $table->char('review_graphics', 2)->nullable();
            $table->char('review_sound', 2)->nullable();
            $table->char('review_gameplay', 2)->nullable();
            $table->char('review_overall', 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('review_score');
    }
}
