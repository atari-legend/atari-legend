<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_vs', function (Blueprint $table) {
            $table->string('lemon64_slug', 255)->nullable();
            $table->index(['atari_id', 'lemon64_slug', 'amiga_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_vs', function (Blueprint $table) {
            $table->dropColumn('lemon64_slug');
            $table->dropIndex(['atari_id', 'lemon64_slug', 'amiga_id']);
        });
    }
};
