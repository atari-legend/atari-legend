<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location', function (Blueprint $table) {
            $table->integer('id', true)->comment('Unique ID of a location');
            $table->char('continent_code', 2)->nullable();
            $table->string('name', 64)->comment('Location name');
            $table->char('country_iso2', 2)->nullable()->comment('Two-letter country code (ISO 3166-1 alpha-2)');
            $table->char('country_iso3', 3)->nullable()->comment('Three-letter country code (ISO 3166-1 alpha-3)');
            $table->enum('type', ['Continent', 'Country'])->comment('Type of location');
            $table->unique(['continent_code', 'country_iso2'], 'continent_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location');
    }
}
