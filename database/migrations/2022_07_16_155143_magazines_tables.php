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
        Schema::create('magazine_index_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
        });
        DB::table('magazine_index_types')
            ->insert([
                ['name' => 'Review'],
                ['name' => 'News'],
                ['name' => 'Tutorial'],
                ['name' => 'Interview'],
                ['name' => 'Reader mail'],
                ['name' => 'Column'],
            ]);

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

        // Empty imgext column as we don't have any magazine images anymore
        DB::table('magazine_issue')
            ->update(['magazine_issue_imgext' => null]);

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

            $table->integer('game_id')->nullable()->change();
            $table->integer('page')->nullable();
            $table->string('title', 1024)->nullable();
            $table->timestamps();

            $table->foreign('magazine_issue_id')->references('id')->on('magazine_issues');
            $table->foreign('game_id')->references('game_id')->on('game');
            $table->foreignId('menu_software_id')->nullable()->constrained();
            $table->foreignId('magazine_index_type_id')->nullable()->constrained();
        });
        Schema::table('magazine_game', function (Blueprint $table) {
            $table->dropColumn('magazine_employe_id');
        });

        Schema::rename('magazine_game', 'magazine_indices');
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
