<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop four columns nothing reads.
 *
 * users.password is written and never read. User::getAuthPasswordName()
 * returns sha512_password, so getAuthPassword() resolves to that column and
 * never to this one, and authentication runs through
 * App\Providers\Auth\UserProvider::validateCredentials(), which compares
 * $user->sha512_password against UserHelper::hashPassword(). The only writer
 * set it to null.
 *
 * users.session holds CPANEL session tokens, users.show_email a toggle no form
 * offers for an address no page renders, and website.user_ip 95 submitter IP
 * addresses that nothing reads and no retention rule covers. Each was named
 * only by a factory or a seeder, which declares rather than reads.
 *
 * None carries an index: users has only PRIMARY and a non-unique index on
 * userid, and website has no index on user_ip.
 *
 * down() re-adds all four with their current types, show_email included as
 * NOT NULL DEFAULT 0. Values are not restored, and the columns come back at
 * the end of their tables rather than at ordinal positions 3, 8 and 18 in
 * users and 10 in website.
 *
 * See docs/plans/2026-08-28-dead-tables-and-columns.md, unit 5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password', 'session', 'show_email']);
        });

        Schema::table('website', function (Blueprint $table) {
            $table->dropColumn('user_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable();
            $table->string('session', 32)->nullable();
            $table->boolean('show_email')->default(0);
        });

        Schema::table('website', function (Blueprint $table) {
            $table->string('user_ip', 32)->nullable();
        });
    }
};
