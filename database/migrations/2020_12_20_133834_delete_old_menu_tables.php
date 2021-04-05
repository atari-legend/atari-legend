<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DeleteOldMenuTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        collect([
            'crew_menu_prod',
            'ind_menu_prod',
            'menu_disk',
            'menu_disk_credits',
            'menu_disk_download',
            'menu_disk_state',
            'menu_disk_submenu',
            'menu_disk_title',
            'menu_disk_title_author',
            'menu_disk_title_demo',
            'menu_disk_title_doc_games',
            'menu_disk_title_doc_tools',
            'menu_disk_title_game',
            'menu_disk_title_music',
            'menu_disk_title_set',
            'menu_disk_title_tools',
            'menu_disk_title_various',
            'menu_disk_year',
            'menu_set',
            'menu_sets_order',
            'menu_stonish',
            'menu_type',
            'menu_types_main',
            'menu_user_comments',
            'screenshot_menu',
        ])->each(function ($table) {
            Schema::dropIfExists($table);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
