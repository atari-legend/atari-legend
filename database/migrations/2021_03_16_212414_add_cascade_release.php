<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCascadeRelease extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite does not support dropping foreign keys
            return;
        }

        Schema::table('game_release_aka', function (Blueprint $table) {
            $table->dropForeign('game_release_aka_ibfk_1');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_aka_ibfk_2');
            $table->foreign('language_id')->references('id')->on('language')->onDelete('cascade');
        });

        Schema::table('game_release_copy_protection', function (Blueprint $table) {
            $table->dropForeign('game_release_copy_protection_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_copy_protection_ibfk_2');
            $table->foreign('copy_protection_id')->references('id')->on('copy_protection')->onDelete('cascade');
        });

        Schema::table('game_release_crew', function (Blueprint $table) {
            $table->dropForeign('game_release_crew_ibfk_1');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_crew_ibfk_2');
            $table->foreign('crew_id')->references('crew_id')->on('crew')->onDelete('cascade');
        });

        Schema::table('game_release_disk_protection', function (Blueprint $table) {
            $table->dropForeign('game_release_disk_protection_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_disk_protection_ibfk_2');
            $table->foreign('disk_protection_id')->references('id')->on('disk_protection')->onDelete('cascade');
        });

        Schema::table('game_release_distributor', function (Blueprint $table) {
            $table->dropForeign('game_release_distributor_ibfk_1');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_distributor_ibfk_2');
            $table->foreign('pub_dev_id')->references('pub_dev_id')->on('pub_dev')->onDelete('cascade');
        });

        Schema::table('game_release_emulator_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_emulator_incompatibility_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_emulator_incompatibility_ibfk_2');
            $table->foreign('emulator_id')->references('id')->on('emulator')->onDelete('cascade');
        });

        Schema::table('game_release_language', function (Blueprint $table) {
            $table->dropForeign('game_release_language_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_language_ibfk_2');
            $table->foreign('language_id')->references('id')->on('language')->onDelete('cascade');
        });

        Schema::table('game_release_location', function (Blueprint $table) {
            $table->dropForeign('game_release_location_ibfk_1');
            $table->foreign('location_id')->references('id')->on('location')->onDelete('cascade');

            $table->dropForeign('game_release_location_ibfk_2');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');
        });

        Schema::table('game_release_memory_enhanced', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_enhanced_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_memory_enhanced_ibfk_2');
            $table->foreign('memory_id')->references('id')->on('memory')->onDelete('cascade');

            $table->dropForeign('game_release_memory_enhanced_ibfk_3');
            $table->foreign('enhancement_id')->references('id')->on('enhancement')->onDelete('cascade');
        });

        Schema::table('game_release_memory_incompatible', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_incompatible_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_memory_incompatible_ibfk_2');
            $table->foreign('memory_id')->references('id')->on('memory')->onDelete('cascade');
        });

        Schema::table('game_release_memory_minimum', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_minimum_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_memory_minimum_ibfk_2');
            $table->foreign('memory_id')->references('id')->on('memory')->onDelete('cascade');
        });

        Schema::table('game_release_resolution', function (Blueprint $table) {
            $table->dropForeign('game_release_resolution_ibfk_1');
            $table->foreign('resolution_id')->references('id')->on('resolution')->onDelete('cascade');

            $table->dropForeign('game_release_resolution_ibfk_2');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');
        });

        Schema::table('game_release_scan', function (Blueprint $table) {
            $table->dropForeign('game_release_scan_ibfk_1');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');
        });

        Schema::table('game_release_system_enhanced', function (Blueprint $table) {
            $table->dropForeign('game_release_system_enhanced_ibfk_1');
            $table->foreign('system_id')->references('id')->on('system')->onDelete('cascade');

            $table->dropForeign('game_release_system_enhanced_ibfk_2');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_system_enhanced_ibfk_3');
            $table->foreign('enhancement_id')->references('id')->on('enhancement')->onDelete('cascade');
        });

        Schema::table('game_release_system_incompatible', function (Blueprint $table) {
            $table->dropForeign('game_release_system_incompatible_ibfk_1');
            $table->foreign('system_id')->references('id')->on('system')->onDelete('cascade');

            $table->dropForeign('game_release_system_incompatible_ibfk_2');
            $table->foreign('game_release_id')->references('id')->on('game_release')->onDelete('cascade');
        });

        Schema::table('game_release_tos_version_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_2');
            $table->foreign('tos_id')->references('id')->on('tos')->onDelete('cascade');

            $table->dropForeign('game_release_tos_version_incompatibility_ibfk_3');
            $table->foreign('language_id')->references('id')->on('language')->onDelete('cascade');
        });

        Schema::table('game_release_trainer_option', function (Blueprint $table) {
            $table->dropForeign('game_release_trainer_option_ibfk_1');
            $table->foreign('release_id')->references('id')->on('game_release')->onDelete('cascade');

            $table->dropForeign('game_release_trainer_option_ibfk_2');
            $table->foreign('trainer_option_id')->references('id')->on('trainer_option')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('game_release_aka', function (Blueprint $table) {
            $table->dropForeign('game_release_aka_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_aka_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_aka_language_id_foreign');
            $table->foreign('language_id', 'game_release_aka_ibfk_2')->references('id')->on('language');
        });

        Schema::table('game_release_copy_protection', function (Blueprint $table) {
            $table->dropForeign('game_release_copy_protection_release_id_foreign');
            $table->foreign('release_id', 'game_release_copy_protection_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_copy_protection_copy_protection_id_foreign');
            $table->foreign('copy_protection_id', 'game_release_copy_protection_ibfk_2')->references('id')->on('copy_protection');
        });

        Schema::table('game_release_crew', function (Blueprint $table) {
            $table->dropForeign('game_release_crew_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_crew_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_crew_crew_id_foreign');
            $table->foreign('crew_id', 'game_release_crew_ibfk_2')->references('crew_id')->on('crew');
        });

        Schema::table('game_release_disk_protection', function (Blueprint $table) {
            $table->dropForeign('game_release_disk_protection_release_id_foreign');
            $table->foreign('release_id', 'game_release_disk_protection_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_disk_protection_disk_protection_id_foreign');
            $table->foreign('disk_protection_id', 'game_release_disk_protection_ibfk_2')->references('id')->on('disk_protection');
        });

        Schema::table('game_release_distributor', function (Blueprint $table) {
            $table->dropForeign('game_release_distributor_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_distributor_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_distributor_pub_dev_id_foreign');
            $table->foreign('pub_dev_id', 'game_release_distributor_ibfk_2')->references('pub_dev_id')->on('pub_dev');
        });

        Schema::table('game_release_emulator_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_emulator_incompatibility_emulator_id_foreign');
            $table->foreign('release_id', 'game_release_emulator_incompatibility_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_emulator_incompatibility_release_id_foreign');
            $table->foreign('emulator_id', 'game_release_emulator_incompatibility_ibfk_2')->references('id')->on('emulator');
        });

        Schema::table('game_release_language', function (Blueprint $table) {
            $table->dropForeign('game_release_language_release_id_foreign');
            $table->foreign('release_id', 'game_release_language_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_language_language_id_foreign');
            $table->foreign('language_id', 'game_release_language_ibfk_2')->references('id')->on('language');
        });

        Schema::table('game_release_location', function (Blueprint $table) {
            $table->dropForeign('game_release_location_location_id_foreign');
            $table->foreign('location_id', 'game_release_location_ibfk_1')->references('id')->on('location');

            $table->dropForeign('game_release_location_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_location_ibfk_2')->references('id')->on('game_release');
        });

        Schema::table('game_release_memory_enhanced', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_enhanced_release_id_foreign');
            $table->foreign('release_id', 'game_release_memory_enhanced_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_memory_enhanced_memory_id_foreign');
            $table->foreign('memory_id', 'game_release_memory_enhanced_ibfk_2')->references('id')->on('memory');

            $table->dropForeign('game_release_memory_enhanced_enhancement_id_foreign');
            $table->foreign('enhancement_id', 'game_release_memory_enhanced_ibfk_3')->references('id')->on('enhancement');
        });

        Schema::table('game_release_memory_incompatible', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_incompatible_release_id_foreign');
            $table->foreign('release_id', 'game_release_memory_incompatible_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_memory_incompatible_memory_id_foreign');
            $table->foreign('memory_id', 'game_release_memory_incompatible_ibfk_2')->references('id')->on('memory');
        });

        Schema::table('game_release_memory_minimum', function (Blueprint $table) {
            $table->dropForeign('game_release_memory_minimum_release_id_foreign');
            $table->foreign('release_id', 'game_release_memory_minimum_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_memory_minimum_memory_id_foreign');
            $table->foreign('memory_id', 'game_release_memory_minimum_ibfk_2')->references('id')->on('memory');
        });

        Schema::table('game_release_resolution', function (Blueprint $table) {
            $table->dropForeign('game_release_resolution_resolution_id_foreign');
            $table->foreign('resolution_id', 'game_release_resolution_ibfk_1')->references('id')->on('resolution');

            $table->dropForeign('game_release_resolution_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_resolution_ibfk_2')->references('id')->on('game_release');
        });

        Schema::table('game_release_scan', function (Blueprint $table) {
            $table->dropForeign('game_release_scan_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_scan_ibfk_1')->references('id')->on('game_release');
        });

        Schema::table('game_release_system_enhanced', function (Blueprint $table) {
            $table->dropForeign('game_release_system_enhanced_system_id_foreign');
            $table->foreign('system_id', 'game_release_system_enhanced_ibfk_1')->references('id')->on('system');

            $table->dropForeign('game_release_system_enhanced_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_system_enhanced_ibfk_2')->references('id')->on('game_release');

            $table->dropForeign('game_release_system_enhanced_enhancement_id_foreign');
            $table->foreign('enhancement_id', 'game_release_system_enhanced_ibfk_3')->references('id')->on('enhancement');
        });

        Schema::table('game_release_system_incompatible', function (Blueprint $table) {
            $table->dropForeign('game_release_system_incompatible_system_id_foreign');
            $table->foreign('system_id', 'game_release_system_incompatible_ibfk_1')->references('id')->on('system');

            $table->dropForeign('game_release_system_incompatible_game_release_id_foreign');
            $table->foreign('game_release_id', 'game_release_system_incompatible_ibfk_2')->references('id')->on('game_release');
        });

        Schema::table('game_release_tos_version_incompatibility', function (Blueprint $table) {
            $table->dropForeign('game_release_tos_version_incompatibility_release_id_foreign');
            $table->foreign('release_id', 'game_release_tos_version_incompatibility_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_tos_version_incompatibility_tos_id_foreign');
            $table->foreign('tos_id', 'game_release_tos_version_incompatibility_ibfk_2')->references('id')->on('tos');

            $table->dropForeign('game_release_tos_version_incompatibility_language_id_foreign');
            $table->foreign('language_id', 'game_release_tos_version_incompatibility_ibfk_3')->references('id')->on('language');
        });

        Schema::table('game_release_trainer_option', function (Blueprint $table) {
            $table->dropForeign('game_release_trainer_option_release_id_foreign');
            $table->foreign('release_id', 'game_release_trainer_option_ibfk_1')->references('id')->on('game_release');

            $table->dropForeign('game_release_trainer_option_trainer_option_id_foreign');
            $table->foreign('trainer_option_id', 'game_release_trainer_option_ibfk_2')->references('id')->on('trainer_option');
        });

    }
}
