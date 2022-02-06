<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpotlightScreenshotForeignKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spotlight', function (Blueprint $table) {
            $table->foreign('screenshot_id')
                ->references('screenshot_id')
                ->on('screenshot_main')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spotlight', function (Blueprint $table) {
            $table->dropForeign('spotlight_screenshot_id_foreign');
        });
    }
}
