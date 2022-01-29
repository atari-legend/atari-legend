<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GameConfigurationConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game_genre_cross', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_genre_cross_ibfk_1');
                $table->dropForeign('game_genre_cross_ibfk_2');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('game_genre_id')
                ->references('id')
                ->on('game_genre')
                ->onDelete('cascade');
        });

        Schema::table('game_engine', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_engine_ibfk_1');
                $table->dropForeign('game_engine_ibfk_2');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('engine_id')
                ->references('id')
                ->on('engine')
                ->onDelete('cascade');
        });

        Schema::table('game_programming_language', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_programming_language_ibfk_1');
                $table->dropForeign('game_programming_language_ibfk_2');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('programming_language_id')
                ->references('id')
                ->on('programming_language')
                ->onDelete('cascade');
        });

        Schema::table('game', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_ibfk_2');
                $table->dropForeign('game_ibfk_3');
            }

            $table->foreign('port_id')
                ->references('id')
                ->on('port')
                ->onDelete('set null');

            $table->foreign('progress_system_id')
                ->references('id')
                ->on('game_progress_system')
                ->onDelete('set null');
        });

        Schema::table('game_control', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_control_ibfk_1');
                $table->dropForeign('game_control_ibfk_2');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('control_id')
                ->references('id')
                ->on('control')
                ->onDelete('cascade');
        });

        Schema::table('game_sound_hardware', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_sound_hardware_ibfk_1');
                $table->dropForeign('game_sound_hardware_ibfk_2');
            }

            $table->foreign('game_id')
                ->references('game_id')
                ->on('game')
                ->onDelete('cascade');

            $table->foreign('sound_hardware_id')
                ->references('id')
                ->on('sound_hardware')
                ->onDelete('cascade');
        });

        Schema::table('dump', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('dump_ibfk_1');
            }

            $table->foreign('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade');
        });

        Schema::table('media_scan', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_scan_ibfk_1');
                $table->dropForeign('media_scan_ibfk_2');
            }

            $table->integer('media_scan_type_id')->nullable()->change();

            $table->foreign('media_id')
                ->references('id')
                ->on('media')
                ->onDelete('cascade');

            $table->foreign('media_scan_type_id')
                ->references('id')
                ->on('media_scan_type')
                ->onDelete('set null');
        });

        Schema::table('media', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_ibfk_2');
            }

            $table->integer('media_type_id')->nullable()->change();

            $table->foreign('media_type_id')
                ->references('id')
                ->on('media_type')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game_genre_cross', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_genre_cross_game_genre_id_foreign');
                $table->dropForeign('game_genre_cross_game_id_foreign');
            }

            $table->foreign('game_id', 'game_genre_cross_ibfk_1')
                ->references('game_id')
                ->on('game')
                ->onDelete('restrict');

            $table->foreign('game_genre_id', 'game_genre_cross_ibfk_2')
                ->references('id')
                ->on('game_genre')
                ->onDelete('restrict');
        });

        Schema::table('game_engine', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_engine_game_id_foreign');
                $table->dropForeign('game_engine_engine_id_foreign');
            }

            $table->foreign('game_id', 'game_engine_ibfk_1')
                ->references('game_id')
                ->on('game')
                ->onDelete('restrict');

            $table->foreign('engine_id', 'game_engine_ibfk_2')
                ->references('id')
                ->on('engine')
                ->onDelete('restrict');
        });

        Schema::table('game_programming_language', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_programming_language_game_id_foreign');
                $table->dropForeign('game_programming_language_programming_language_id_foreign');
            }

            $table->foreign('game_id', 'game_programming_language_ibfk_1')
                ->references('game_id')
                ->on('game')
                ->onDelete('restrict');

            $table->foreign('programming_language_id', 'game_programming_language_ibfk_2')
                ->references('id')
                ->on('programming_language')
                ->onDelete('restrict');
        });

        Schema::table('game', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_port_id_foreign');
                $table->dropForeign('game_progress_system_id_foreign');
            }

            $table->foreign('port_id', 'game_ibfk_2')
                ->references('id')
                ->on('port')
                ->onDelete('restrict');

            $table->foreign('progress_system_id', 'game_ibfk_3')
                ->references('id')
                ->on('game_progress_system')
                ->onDelete('restrict');
        });

        Schema::table('game_control', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_control_game_id_foreign');
                $table->dropForeign('game_control_control_id_foreign');
            }

            $table->foreign('game_id', 'game_control_ibfk_2')
                ->references('game_id')
                ->on('game')
                ->onDelete('restrict');

            $table->foreign('control_id', 'game_control_ibfk_1')
                ->references('id')
                ->on('control')
                ->onDelete('restrict');
        });

        Schema::table('game_sound_hardware', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('game_sound_hardware_game_id_foreign');
                $table->dropForeign('game_sound_hardware_sound_hardware_id_foreign');
            }

            $table->foreign('game_id', 'game_sound_hardware_ibfk_1')
                ->references('game_id')
                ->on('game')
                ->onDelete('restrict');

            $table->foreign('sound_hardware_id', 'game_sound_hardware_ibfk_2')
                ->references('id')
                ->on('sound_hardware')
                ->onDelete('restrict');
        });

        Schema::table('dump', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('dump_media_id_foreign');
            }

            $table->foreign('media_id', 'dump_ibfk_1')
                ->references('id')
                ->on('media')
                ->onDelete('restrict');
        });

        Schema::table('media_scan', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_scan_media_id_foreign');
                $table->dropForeign('media_scan_media_scan_type_id_foreign');
            }

            $table->foreign('media_id', 'media_scan_ibfk_1')
                ->references('id')
                ->on('media')
                ->onDelete('restrict');

            $table->foreign('media_scan_type_id', 'media_scan_ibfk_2')
                ->references('id')
                ->on('media_scan_type')
                ->onDelete('restrict');
        });

        Schema::table('media', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('media_media_type_id_foreign');
            }

            $table->foreign('media_type_id', 'media_ibfk_2')
                ->references('id')
                ->on('media_type')
                ->onDelete('restrict');
        });
    }
}
