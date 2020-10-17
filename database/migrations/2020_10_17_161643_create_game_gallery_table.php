<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameGalleryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_gallery', function (Blueprint $table) {
            $table->integer('game_gallery_id', true);
            $table->integer('game_id')->default(0);
            $table->string('image_ext', 11)->nullable();
            $table->text('game_description_gallery')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_gallery');
    }
}
