<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenameDeveloperRoleColumn extends Migration
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
            Schema::table('developer_role', function (Blueprint $table) {
                $table->string('name', 50)->nullable();
            });
            DB::table('developer_role')
                ->update(['name' => DB::raw('role')]);
            Schema::table('developer_role', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        } else {
            Schema::table('developer_role', function (Blueprint $table) {
                $table->renameColumn('role', 'name');
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
            Schema::table('developer_role', function (Blueprint $table) {
                $table->string('role', 50);
            });
            DB::table('developer_role')
                ->update(['role' => DB::raw('name')]);
            Schema::table('developer_role', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        } else {
            Schema::table('developer_role', function (Blueprint $table) {
                $table->renameColumn('name', 'role');
            });
        }
    }
}
