<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DeleteDocTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ([
            'doc',
            'doc_category',
            'doc_disk_game',
            'doc_disk_tool',
            'doc_game',
            'doc_type',
        ] as $table) {
            Schema::drop($table);
        }
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
