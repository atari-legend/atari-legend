<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the two pre-Laravel authentication tables, superseded years ago.
 *
 * users_reset (22 rows, 2017-10-01 to 2020-07-14) is superseded by
 * password_resets, which config/auth.php names and which holds rows dated this
 * month. users_login_attempts (506 rows, 2017-09-20 to 2024-06-10) is
 * superseded by Laravel's login throttle, which counts attempts in the cache
 * store rather than in a table.
 *
 * Neither has a model, a relation, or a reference outside the historical
 * create_* migrations, and neither carries a foreign key, so a users row
 * deletes without touching them. Nothing in vendor/ knows either name, so no
 * package can reach them by convention, and the schema has no trigger, view,
 * stored routine or event. The writer was CPANEL, retired 2026-08-22, which is
 * what the handful of post-Laravel rows are.
 *
 * down() restores structure, never rows. users_login_attempts has no primary
 * key and the recreation keeps it that way.
 *
 * See docs/plans/2026-08-28-dead-tables-and-columns.md, unit 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('users_reset');
        Schema::dropIfExists('users_login_attempts');
    }

    public function down(): void
    {
        Schema::create('users_reset', function (Blueprint $table) {
            $table->integer('reset_id', true);
            $table->integer('user_id');
            $table->string('password', 128)->nullable();
            $table->string('time', 32)->nullable();
        });

        Schema::create('users_login_attempts', function (Blueprint $table) {
            $table->integer('user_id');
            $table->string('time', 30)->nullable();
        });
    }
};
