<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DeleteDemoTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ([
            'demo',
            'crew_demo_prod',
            'demo_aka',
            'demo_author',
            'demo_cat',
            'demo_cat_cross',
            'demo_download',
            'demo_emulator',
            'demo_falcon_enhan',
            'demo_falcon_only',
            'demo_info',
            'demo_mono_only',
            'demo_music',
            'demo_series',
            'demo_series_cross',
            'demo_ste_enhan',
            'demo_ste_only',
            'demo_submitinfo',
            'demo_user_comments',
            'demo_year',
            'ind_demo_prod',
            'review_demo',
            'screenshot_demo',
        ] as $table) {
            Schema::drop($table);
        }

        Storage::disk('public')->deleteDirectory('images/demo_screenshots/');
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
