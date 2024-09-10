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
        Schema::table('magazine_issues', function (Blueprint $table) {
            $table->integer('page_count')->nullable();
            $table->integer('circulation')->nullable();
            $table->string('label', 256)->nullable();
            $table->integer('issue')
                ->nullable(true)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('magazine_issues', function (Blueprint $table) {
            $table->dropColumn('page_count');
            $table->dropColumn('circulation');
            $table->dropColumn('label');
            $table->integer('issue')
                ->nullable(false)
                ->default(0)
                ->change();
        });
    }
};
