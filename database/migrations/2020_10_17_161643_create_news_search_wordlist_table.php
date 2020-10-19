<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNewsSearchWordlistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_search_wordlist', function (Blueprint $table) {
            $table->mediumInteger('news_word_id');
            $table->string('news_word_text', 50)->default('')->primary();
        });

        // FIXME: Implement for other drivers?
        if (DB::connection()->getDriverName() === 'mysql') {
            // Add auto-increment to the ID column
            DB::statement('ALTER TABLE `news_search_wordlist` ADD KEY `news_word_id` (`news_word_id`)');
            DB::statement('ALTER TABLE `news_search_wordlist` MODIFY `news_word_id` mediumint AUTO_INCREMENT');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_search_wordlist');
    }
}
