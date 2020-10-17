<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('article_text', function (Blueprint $table) {
            $table->integer('article_text_id', true);
            $table->integer('article_id')->nullable();
            $table->text('article_title')->nullable();
            $table->text('article_text')->nullable();
            $table->integer('article_date')->nullable();
            $table->text('article_intro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('article_text');
    }
}
