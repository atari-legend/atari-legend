<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('user_id', true);
            $table->string('userid')->nullable()->index();
            $table->string('password')->nullable();
            $table->char('sha512_password', 128)->nullable();
            $table->char('salt', 128)->nullable();
            $table->string('email')->nullable();
            $table->integer('permission')->nullable();
            $table->string('session', 32)->nullable();
            $table->string('join_date', 32)->nullable();
            $table->string('last_visit', 32)->nullable();
            $table->string('user_website', 128)->nullable();
            $table->string('user_fb', 128)->nullable();
            $table->string('user_twitter', 128)->nullable();
            $table->string('user_af', 128)->nullable();
            $table->integer('karma')->nullable();
            $table->string('avatar_ext', 11)->nullable();
            $table->boolean('inactive')->default(0)->comment('Make user account inactive');
            $table->boolean('show_email')->default(0)->comment('Display email address at comments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
