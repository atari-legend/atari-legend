<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // The renameColumn calls as in separate blueprints to be compatible
        // with SQLite for unit tests

        Schema::table('magazine', function (Blueprint $table) {
            $table->renameColumn('magazine_id', 'id');

            $table->timestamps();
        });
        Schema::table('magazine', function (Blueprint $table) {
            $table->renameColumn('magazine_name', 'name');
        });
        Schema::rename('magazine', 'magazines');

        Schema::table('magazine_issue', function (Blueprint $table) {
            $table->renameColumn('magazine_issue_id', 'id');

            $table->date('published')->nullable();
            $table->text('archiveorg_url')->nullable();
            $table->integer('barcode')->nullable();
            $table->timestamps();

            $table->foreign('magazine_id')->references('id')->on('magazines');
        });
        Schema::table('magazine_issue', function (Blueprint $table) {
            $table->renameColumn('magazine_issue_nr', 'issue');
        });
        Schema::table('magazine_issue', function (Blueprint $table) {
            $table->renameColumn('magazine_issue_imgext', 'imgext');
        });
        Schema::rename('magazine_issue', 'magazine_issues');

        // Delete rows pointing to a game that doesn't exist anymore
        DB::table('magazine_game')
            ->whereRaw('game_id not in (select game_id from game)')
            ->delete();

        Schema::table('magazine_game', function (Blueprint $table) {
            $table->renameColumn('magazine_game_id', 'id');

            $table->integer('page')->nullable();
            $table->timestamps();

            $table->foreign('magazine_issue_id')->references('id')->on('magazine_issues');
            $table->foreign('game_id')->references('game_id')->on('game');
            $table->foreignId('menu_software_id')->nullable()->constrained();
        });
        Schema::table('magazine_game', function (Blueprint $table) {
            $table->renameColumn('magazine_issue_id', 'issue');
        });

        Schema::table('magazine_game', function (Blueprint $table) {
            $table->dropColumn('magazine_employe_id');
        });

        Schema::rename('magazine_game', 'magazine_indexes');
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
};
