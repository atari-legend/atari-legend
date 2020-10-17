<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGameReleaseTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_release', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a release');
            $table->integer('game_id')->index()->comment('ID of the game the release is for');
            $table->string('name')->nullable()->comment('Optional alternative name of the release');
            $table->date('date')->nullable()->comment('Release date');
            $table->enum('license', ['Commercial', 'Non-Commercial'])->nullable();
            $table->enum('type', ['Re-release', 'Budget', 'Budget re-release', 'Playable demo', 'Non-playable demo', 'Slideshow', 'Unofficial', 'Data disk', 'Review copy'])->nullable();
            $table->integer('pub_dev_id')->nullable()->index()->comment('Publisher of the release');
            $table->boolean('hd_installable')->nullable()->comment('HD installable or not');
            $table->enum('status', ['Unfinished', 'Development', 'Unreleased'])->nullable()->comment('status of the release');
            $table->text('notes')->nullable()->comment('add a note to a release');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('game_release');
    }
}
