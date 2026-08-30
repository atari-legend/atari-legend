<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise the sixteen game reference tables.
 *
 * All sixteen are lookup tables, and all sixteen are the target of at least
 * one foreign key, so InnoDB rewrites every child's referenced name and no
 * child table is touched here.
 *
 * Two of the targets are not the old name with an `s` on the end.
 * Str::plural('Memory') is 'Memories', so `memory` becomes `memories` rather
 * than `memorys`. And `trainer_option` becomes `trainer_options`, not
 * `trainers`: the plan's table proposed dropping the word, and the campaign's
 * rule -- a table keeps its words, and the model class moves until it derives
 * the table -- says otherwise. Trainer -> TrainerOption in the same commit,
 * and the reward is that game_release_trainer_option.trainer_option_id keeps
 * matching *foreign key = singularised referenced-table name + _id*, so the
 * column needs no follow-up and GameRelease::trainers() sheds both key
 * arguments rather than keeping one forever.
 *
 * See docs/plans/2026-08-29-plural-table-rename.md, Unit 3, and
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'control'              => 'controls',
        'copy_protection'      => 'copy_protections',
        'disk_protection'      => 'disk_protections',
        'emulator'             => 'emulators',
        'engine'               => 'engines',
        'enhancement'          => 'enhancements',
        'language'             => 'languages',
        'location'             => 'locations',
        'memory'               => 'memories',
        'port'                 => 'ports',
        'resolution'           => 'resolutions',
        'system'               => 'systems',
        'trainer_option'       => 'trainer_options',
        'article_type'         => 'article_types',
        'news_image'           => 'news_images',
        'programming_language' => 'programming_languages',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);
    }

    public function down(): void
    {
        TableRenamer::reverse(self::TABLES);
    }
};
