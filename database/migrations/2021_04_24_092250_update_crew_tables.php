<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateCrewTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crew', function (Blueprint $table) {
            // Info column is empty and redundant with history
            $table->dropColumn('crew_info');
            // Make name non-nullable
            $table->string('crew_name', 255)->nullable(false)->change();
        });

        DB::table('crew_individual')
            ->where('individual_nicks_id', '=', 0)
            ->update([
                'individual_nicks_id' => null,
            ]);
        Schema::table('crew_individual', function (Blueprint $table) {
            $table->foreign('crew_id')->references('crew_id')->on('crew')->onDelete('cascade');
            $table->foreign('ind_id')->references('ind_id')->on('individuals')->onDelete('cascade');
            $table->foreign('individual_nicks_id')->references('individual_nicks_id')->on('individual_nicks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crew', function (Blueprint $table) {
            $table->text('crew_info')->nullable();
            $table->string('crew_name', 255)->nullable()->change();
        });
        Schema::table('crew_individual', function (Blueprint $table) {
            $table->dropForeign('crew_individual_crew_id_foreign');
            $table->dropForeign('crew_individual_ind_id_foreign');
            $table->dropForeign('crew_individual_individual_nicks_id_foreign');
        });
    }
}
