<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameMemoryColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite does not support renaming columns
            Schema::table('memory', function (Blueprint $table) {
                $table->string('name', 45);
            });
            DB::table('memory')
                ->update(['name' => DB::raw('memory')]);
            Schema::table('memory', function (Blueprint $table) {
                $table->dropColumn('memory');
            });
        } else {
            Schema::table('memory', function (Blueprint $table) {
                $table->renameColumn('memory', 'name');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite does not support renaming columns
            Schema::table('memory', function (Blueprint $table) {
                $table->string('memory', 45);
            });
            DB::table('memory')
                ->update(['memory' => DB::raw('name')]);
            Schema::table('memory', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        } else {
            Schema::table('memory', function (Blueprint $table) {
                $table->renameColumn('name', 'memory');
            });
        }
    }
}
