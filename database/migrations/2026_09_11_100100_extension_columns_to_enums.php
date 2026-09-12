<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The twelve image extension columns become enums of exactly what each accepts.
 *
 * They carried six distinct types between them - varchar(4) to varchar(255) -
 * for a value that is never longer than four characters, and only
 * game_release_scans was already an enum. An unlisted extension is now a
 * database error rather than a row nothing can render; the form rejection that
 * keeps it from getting that far shipped in Unit 1, which is where the member
 * lists live as EXTENSIONS constants on the models.
 *
 * Wherever jpg is a member, jpeg is a member. The two spell one format, and
 * magazine_issues stores jpeg for all 66 of its rows because
 * MagazineIssuesController::fetchImage() splits it out of an image/jpeg
 * response header while every other column is written from
 * UploadedFile::extension(), which maps that type to jpg. Pairing the spellings
 * is what converts magazine_issues without renaming its files on disk.
 *
 * screenshots carries fifteen members because the public game submission form
 * writes it and contributors send disk images and map archives through it.
 *
 * Skipped on SQLite, where the suite runs. SQLite has no ENUM: Laravel renders
 * one as a CHECK constraint, and reaching it has to rebuild the table - which
 * re-adds every foreign key the table already has, a second time and with ON
 * DELETE RESTRICT, so deleting a media row would start failing in tests for a
 * reason that has nothing to do with an extension. The enum is a MariaDB
 * guarantee; Unit 1's validation is what the suite gates.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 2.
 */
return new class extends Migration
{
    /**
     * Table => [enum members, nullable].
     */
    private const ENUMS = [
        'screenshots'           => [['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'zip', 'pdf', 'txt', 'bin', 'st', 'msa', 'stx', 'raw', 'scp'], true],
        'menu_disk_screenshots' => [['png', 'jpg', 'jpeg', 'bmp'], false],
        'game_release_scans'    => [['png', 'jpg', 'jpeg'], false],
        'news_images'           => [['png', 'jpg', 'jpeg', 'gif', 'webp'], true],
        'links'                 => [['png', 'jpg', 'jpeg'], true],
        'companies'             => [['png', 'jpg', 'jpeg', 'gif', 'webp'], true],
        'individuals'           => [['png', 'jpg', 'jpeg', 'webp'], true],
        'magazine_issues'       => [['png', 'jpg', 'jpeg'], true],
        'media_scans'           => [['jpg', 'jpeg', 'png'], true],
        'crews'                 => [['png', 'jpg', 'jpeg', 'gif', 'webp'], true],
        'users'                 => [['png', 'jpg', 'jpeg', 'gif'], true],
        'game_galleries'        => [['png', 'jpg', 'jpeg'], true],
    ];

    /**
     * Table => [type this column had before, nullable], for down().
     */
    private const ORIGINALS = [
        'screenshots'           => [11, true],
        'menu_disk_screenshots' => [4, false],
        'news_images'           => [64, true],
        'links'                 => [11, true],
        'companies'             => [50, true],
        'individuals'           => [50, true],
        'magazine_issues'       => [11, true],
        'media_scans'           => [11, true],
        'crews'                 => [255, true],
        'users'                 => [11, true],
        'game_galleries'        => [11, true],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (self::ENUMS as $table => [$members, $nullable]) {
            Schema::table($table, function (Blueprint $t) use ($members, $nullable) {
                $t->enum('imgext', $members)->nullable($nullable)->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // game_release_scans is left out: it was an enum already, and the same
        // one.
        foreach (self::ORIGINALS as $table => [$length, $nullable]) {
            Schema::table($table, function (Blueprint $t) use ($length, $nullable) {
                $t->string('imgext', $length)->nullable($nullable)->change();
            });
        }
    }
};
