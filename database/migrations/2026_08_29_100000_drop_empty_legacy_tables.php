<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop seven tables that hold nothing and that nothing reaches.
 *
 * None has a model, a relation, or a reference outside the historical
 * create_* migrations, and every one measures zero rows. dump_user_info holds
 * two RESTRICT foreign keys into dump and users, which is why
 * User::getIsDeletableAttribute() consulted it; it blocks no account, and the
 * check goes in the same commit.
 *
 * personal_access_tokens exists only on the production lineage -- no migration
 * creates it, and Sanctum is not installed -- so up() drops it conditionally
 * while down() recreates it unconditionally, which is what the production
 * rollback this down() serves needs.
 *
 * down() restores structure, never rows. All seven are empty, so nothing is
 * lost.
 *
 * See docs/plans/2026-08-28-dead-tables-and-columns.md, unit 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['theme', 'theme_style', 'theme_template', 'tools', 'gameinfo_screenshot', 'dump_user_info', 'personal_access_tokens'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        Schema::create('theme', function (Blueprint $table) {
            $table->integer('theme_id', true);
            $table->string('theme_name');
            $table->integer('theme_style_id');
            $table->integer('theme_template_id');
        });

        Schema::create('theme_style', function (Blueprint $table) {
            $table->integer('theme_style_id', true);
            $table->string('theme_style_name');
        });

        Schema::create('theme_template', function (Blueprint $table) {
            $table->integer('theme_template_id', true);
            $table->string('theme_template_name');
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->integer('tools_id', true);
            $table->string('tools_name');
            $table->integer('stonish_id');
        });

        Schema::create('gameinfo_screenshot', function (Blueprint $table) {
            $table->integer('gameinfo_screenshot_id', true);
            $table->integer('game_id')->nullable();
            $table->integer('screenshot_id')->nullable();
        });

        Schema::create('dump_user_info', function (Blueprint $table) {
            $table->comment('Stores who downloaded a dump and when');
            $table->integer('id', true)->comment('Unique ID of a dump');
            $table->integer('dump_id')->index('dump_id')->comment('Foreign key to dump table');
            $table->integer('user_id')->index('user_id')->comment('Foreign key to user table');
            $table->date('date')->nullable()->comment('Date when dump was downloaded');
        });

        Schema::table('dump_user_info', function (Blueprint $table) {
            $table->foreign('dump_id', 'dump_user_info_ibfk_1')->references('id')->on('dump')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('user_id', 'dump_user_info_ibfk_2')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->unsignedBigInteger('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};
