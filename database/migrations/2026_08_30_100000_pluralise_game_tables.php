<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise the ten game and release tables.
 *
 * Every name here keeps its words; only case and plural are normalised. That
 * matters most for game_genre and game_progress_system, whose children carry
 * game_genre_id and game_progress_system_id: those columns were the
 * foreign-key campaign's own moves, and singularising game_genres still gives
 * game_genre, so the campaign's rule -- foreign key = singularised
 * referenced-table name + _id -- keeps holding. Renaming the tables to
 * `genres` and `progress_systems` instead would have broken both.
 *
 * game_submitinfo -> game_submit_infos is the one target that is not the old
 * string with a suffix, because `submitinfo` was never snake-cased and
 * Str::snake('GameSubmitInfo') splits it. The consequence is one column,
 * screenshot_game_submitinfo.game_submitinfo_id, that stops matching the rule
 * above; it and its pivot are a follow-up, recorded in the plan's Out of
 * scope, not a thing this migration touches.
 *
 * `tos` is deliberately absent. TOS -> Tos is a class rename that lands in the
 * same commit and derives the table the schema already has, which is the whole
 * of why it was worth doing.
 *
 * game and game_release are the two heaviest foreign-key parents in the
 * schema -- together the target of forty-one of its hundred and forty-two
 * constraints -- and not one child table is touched here, because InnoDB
 * rewrites the referenced name in a foreign key when its parent is renamed.
 *
 * See docs/plans/2026-08-29-plural-table-rename.md, Unit 2, and
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'game'                                     => 'games',
        'game_aka'                                 => 'game_akas',
        'game_fact'                                => 'game_facts',
        'game_release'                             => 'game_releases',
        'game_submitinfo'                          => 'game_submit_infos',
        'game_release_aka'                         => 'game_release_akas',
        'game_release_scan'                        => 'game_release_scans',
        'game_genre'                               => 'game_genres',
        'game_progress_system'                     => 'game_progress_systems',
        'game_release_tos_version_incompatibility' => 'game_release_tos_version_incompatibilities',
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
