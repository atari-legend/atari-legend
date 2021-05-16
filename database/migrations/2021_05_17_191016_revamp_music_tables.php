<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevampMusicTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ([
            'game_music',
            'music_author',
            'music_types',
            'music',
            'music_types_main',
        ] as $table) {
            Schema::drop($table);
        }

        Schema::create('sndhs', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('title', 255)->nullable();
            $table->string('composer', 255)->nullable();
            $table->string('ripper', 255)->nullable();
            $table->integer('subtunes')->nullable();
            $table->integer('default_subtune')->nullable();
            $table->integer('year')->nullable();
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sndhs ADD FULLTEXT title_search (title)');
        }

        Schema::create('game_sndh', function (Blueprint $table) {
            $table->integer('game_id');
            $table->string('sndh_id', 255);

            $table->primary(['game_id', 'sndh_id']);
            $table->foreign('game_id')->references('game_id')->on('game')->onDelete('cascade');
            $table->foreign('sndh_id')->references('id')->on('sndhs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sndhs');
        Schema::drop('game_sndh');
    }
}
