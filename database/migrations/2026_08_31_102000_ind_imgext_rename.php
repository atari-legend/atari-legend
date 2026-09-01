<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename individuals.ind_imgext to imgext.
 *
 * The avatar's file extension. `imgext` is the schema's majority spelling,
 * shared with game_release_scans, magazine_issues, media_scans,
 * menu_disk_screenshots and screenshots. The two remaining outliers,
 * game_gallery.image_ext and users.avatar_ext, are deliberately left.
 *
 * Unit 5 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('ind_imgext', 'imgext'));
    }

    public function down(): void
    {
        Schema::table('individuals', fn (Blueprint $t) => $t->renameColumn('imgext', 'ind_imgext'));
    }
};
