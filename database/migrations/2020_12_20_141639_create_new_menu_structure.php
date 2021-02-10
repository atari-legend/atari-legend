<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNewMenuStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_sets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 64);
            $table->enum('menus_sort', ['asc', 'desc'])
                ->default('asc')
                ->comment('How to sort menus of this set');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_sets` comment 'Sets of menus'");
        }

        Schema::create('crew_menu_set', function (Blueprint $table) {
            $table->integer('crew_id');
            $table->foreignId('menu_set_id')->constrained()->cascadeOnDelete();
            $table->primary('crew_id', 'menu_set_id');

            $table->foreign('crew_id')->references('crew_id')->on('crew');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `crew_menu_set` comment 'Pivot between menu sets and crews'");
        }

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('number')->nullable()->comment('Sequence number within the menu set');
            $table->string('issue', 16)->nullable()->comment('Menu issue (number or letter, etc.)');
            $table->date('date')->nullable()->comment('Release date');
            $table->string('version', 8)->nullable();
            $table->foreignId('menu_set_id')->constrained();
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menus` comment 'An individual menu'");
        }

        Schema::create('menu_disk_conditions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 64);
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_disk_conditions` comment 'Condition of a menu disk'");
        }
        DB::table('menu_disk_conditions')->insert([
            ['name' => 'Missing', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Intro only or partially damaged', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Slightly damaged', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Intact', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        Schema::create('menu_disk_dumps', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->enum('format', ['STX', 'MSA', 'RAW', 'SCP', 'ST'])->nullable();
            $table->string('sha512', 128)->nullable();
            $table->integer('size')->nullable()->comment('File size in bytes');
            // No constraint on user as users can get deleted
            $table->integer('user_id')->nullable()->comment('User who uploaded the dump');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_disk_dumps` comment 'Dump of the physical menu disk'");
        }

        Schema::create('menu_disks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('menu_id')->constrained();
            $table->string('part', 16)->nullable()->comment('Arbitrary part identifier (e.g. A, B, C, or Part I, Part II, …)');
            $table->text('scrolltext')->nullable()->comment('Content of the scrolltext');
            $table->integer('donated_by_individual_id')->nullable()->comment('Who donated this menu/dump');
            $table->foreignId('menu_disk_condition_id')->nullable()->constrained();
            $table->foreignId('menu_disk_dump_id')->nullable()->constrained();
            $table->string('notes', 512)->nullable();

            $table->foreign('donated_by_individual_id')->references('ind_id')->on('individuals');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_disks` comment 'A single disk part of a menu'");
        }

        Schema::create('menu_disk_screenshots', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('menu_disk_id')->constrained();
            $table->string('imgext', 4);
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_disk_screenshots` comment 'Screenshots of a menu disk'");
        }

        Schema::create('menu_software_content_types', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name', 64);
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_software_content_types` comment 'Type of software part of a menu disk'");
        }
        DB::table('menu_software_content_types')->insert([
            ['name' => 'Game', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Demo', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Utility', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Music', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Source code', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Picture', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'E-zine / Documentation', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ]);

        Schema::create('menu_software', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('menu_software_content_type_id')->constrained();
            $table->string('name', 255);
            $table->integer('demozoo_id')->nullable()->comment('ID of the DemoZoo production');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_software` comment 'Non-game software present on menus'");
        }

        Schema::create('menu_disk_contents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('menu_disk_id')->constrained();
            $table->tinyInteger('order');
            $table->integer('game_release_id')->nullable();
            $table->integer('game_id')->nullable();
            $table->foreignId('menu_software_id')->nullable()->constrainted();
            $table->string('subtype', 64)->nullable();
            $table->string('version', 64)->nullable();
            $table->string('requirements', 64)->nullable();

            $table->foreign('game_release_id')->references('id')->on('game_release');
            $table->foreign('game_id')->references('game_id')->on('game');
        });
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `menu_disk_contents` comment 'A software present on a menu disk'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        DB::table('menu_disk_contents')->delete();
        DB::table('menu_software')->delete();
        DB::table('menu_software_content_types')->delete();
        DB::table('menu_disk_screenshots')->delete();
        DB::table('menu_disks')->delete();
        DB::table('menu_disk_conditions')->delete();
        DB::table('menu_disk_dumps')->delete();
        DB::table('menus')->delete();
        DB::table('crew_menu_set')->delete();
        DB::table('menu_sets')->delete();
        Schema::dropIfExists('menu_disk_contents');
        Schema::dropIfExists('menu_software');
        Schema::dropIfExists('menu_software_content_types');
        Schema::dropIfExists('menu_disk_screenshots');
        Schema::dropIfExists('menu_disks');
        Schema::dropIfExists('menu_disk_conditions');
        Schema::dropIfExists('menu_disk_dumps');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('crew_menu_set');
        Schema::dropIfExists('menu_sets');
    }
}
