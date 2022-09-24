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
        Schema::table('magazine_indices', function (Blueprint $table) {
            $table->integer('individual_id')
                ->nullable()
                ->references('ind_id')
                ->on('individuals')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('magazine_indices', function (Blueprint $table) {
            $table->dropColumn('individual_id');
        });
    }
};
