<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuDiskTitleAuthorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_disk_title_author', function (Blueprint $table) {
            $table->integer('menu_disk_title_author_id', true);
            $table->integer('menu_disk_title_id');
            $table->integer('ind_id');
            $table->integer('author_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menu_disk_title_author');
    }
}
