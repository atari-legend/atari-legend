<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_ibfk_1');
            }

            $table->foreign('release_id')
                ->references('id')
                ->on('game_release')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_release_id_foreign');
            }

            $table->foreign('release_id', 'media_ibfk_1')
                ->references('id')
                ->on('game_release')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }
};
