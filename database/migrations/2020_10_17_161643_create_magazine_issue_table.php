<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMagazineIssueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('magazine_issue', function (Blueprint $table) {
            $table->integer('magazine_issue_id', true);
            $table->integer('magazine_issue_nr')->default(0);
            $table->string('magazine_issue_imgext', 11)->nullable();
            $table->integer('magazine_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('magazine_issue');
    }
}
