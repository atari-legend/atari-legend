<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBugReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bug_report', function (Blueprint $table) {
            $table->integer('bug_report_id', true);
            $table->integer('bug_report_type_id')->nullable();
            $table->text('bug_report_text')->nullable();
            $table->integer('bug_report_date')->nullable();
            $table->integer('user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bug_report');
    }
}
