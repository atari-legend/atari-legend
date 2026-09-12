<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every column called `name` becomes varchar(255).
 *
 * 42 tables carry one, in seven distinct types between varchar(45) and
 * varchar(256), for the same kind of value: the label of the thing the row is.
 * varchar(255) is the largest of the seven groups, it is what Schema::string()
 * produces without a length, and no column loses capacity - 32 widen and two
 * narrow by one character from varchar(256), where the longest values are 37
 * and 15 characters. The longest `name` anywhere is 81, in games.
 *
 * Eight tables are already varchar(255) and are left out; the census in the
 * plan is what proves they are.
 *
 * Nullability is not touched. Nineteen of the 42 are nullable, including lookup
 * tables where a null label is meaningless, and that is a question about each
 * table's data rather than about its type.
 *
 * Skipped on SQLite, where the suite runs: it does not enforce a varchar
 * length, so the change would be a no-op there - one that still rebuilds each
 * table and re-orders the foreign keys it carries.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 4.
 */
return new class extends Migration
{
    /**
     * Table => [length it had before, nullable].
     */
    private const ORIGINALS = [
        'andreas'                     => [50, true],
        'article_types'               => [50, true],
        'categories'                  => [128, true],
        'controls'                    => [45, false],
        'copy_protections'            => [45, false],
        'developer_roles'             => [50, true],
        'disk_protections'            => [45, false],
        'emulators'                   => [45, false],
        'engines'                     => [64, false],
        'enhancements'                => [250, false],
        'game_akas'                   => [128, true],
        'game_progress_systems'       => [250, false],
        'game_release_akas'           => [256, true],
        'game_series'                 => [128, false],
        'genres'                      => [128, true],
        'individual_roles'            => [128, true],
        'languages'                   => [64, true],
        'links'                       => [128, true],
        'link_submissions'            => [128, true],
        'locations'                   => [64, false],
        'magazines'                   => [128, true],
        'magazine_index_types'        => [64, false],
        'media_scan_types'            => [250, false],
        'media_types'                 => [250, false],
        'memories'                    => [45, false],
        'menu_disk_conditions'        => [64, false],
        'menu_sets'                   => [64, false],
        'menu_software_content_types' => [64, false],
        'news_images'                 => [64, true],
        'ports'                       => [45, false],
        'programming_languages'       => [64, false],
        'sound_hardware'              => [64, false],
        'tos'                         => [50, true],
        'trainer_options'             => [256, true],
    ];

    public function up(): void
    {
        $this->resize(fn () => 255);
    }

    public function down(): void
    {
        $this->resize(fn (int $length) => $length);
    }

    private function resize(callable $length): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        foreach (self::ORIGINALS as $table => [$was, $nullable]) {
            Schema::table($table, function (Blueprint $t) use ($length, $was, $nullable) {
                $t->string('name', $length($was))->nullable($nullable)->change();
            });
        }
    }
};
