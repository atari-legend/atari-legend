<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last three image extension columns take the name the other nine carry.
 *
 * game_galleries.image_ext and users.avatar_ext are the two outliers the
 * 2026-08-31 column name sweep deliberately left; crews.logo came out of that
 * sweep's prefix stripping and names what the file is rather than what the
 * column holds. Company already has the shape the other two take: the column
 * is imgext and the reading accessors are file, path and logo.
 *
 * The renames travel with the enum conversion because that migration rewrites
 * all three columns anyway.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_galleries', fn (Blueprint $t) => $t->renameColumn('image_ext', 'imgext'));
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('avatar_ext', 'imgext'));
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('logo', 'imgext'));
    }

    public function down(): void
    {
        Schema::table('game_galleries', fn (Blueprint $t) => $t->renameColumn('imgext', 'image_ext'));
        Schema::table('users', fn (Blueprint $t) => $t->renameColumn('imgext', 'avatar_ext'));
        Schema::table('crews', fn (Blueprint $t) => $t->renameColumn('imgext', 'logo'));
    }
};
