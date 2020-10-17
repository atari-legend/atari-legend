<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInterviewTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interview_text', function (Blueprint $table) {
            $table->integer('interview_text_id', true);
            $table->integer('interview_id')->nullable();
            $table->text('interview_text')->nullable();
            $table->integer('interview_date')->nullable();
            $table->text('interview_intro')->nullable();
            $table->text('interview_chapters')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interview_text');
    }
}
