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
        Schema::create('sndh_archives', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->string('download_url', 1024);
        });
        DB::table('sndh_archives')->insert([
            'id'           => 'sndh45lf',
            'download_url' => 'https://sndh.atari.org/files/sndh45lf.zip',
        ]);

        Schema::table('sndhs', function (Blueprint $table) {
            $table->string('sndh_archive_id', 255)->nullable();
        });

        DB::table('sndhs')->update(['sndh_archive_id' => 'sndh45lf']);

        Schema::table('sndhs', function (Blueprint $table) {
            $table->string('sndh_archive_id', 255)->nullable(false)->change();

            $table->foreign('sndh_archive_id')
                ->references('id')
                ->on('sndh_archives')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sndhs', function (Blueprint $table) {
            $table->dropForeign('sndhs_sndh_archive_id_foreign');
            $table->dropColumn('sndh_archive_id');
        });
        Schema::drop('sndh_archives');
    }
};
