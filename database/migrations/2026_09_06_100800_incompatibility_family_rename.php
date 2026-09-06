<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Two spellings of "incompatible" close; the third, Rule 2 working rather
 * than drift, is left.
 *
 * game_release_emulator_incompatibility is a pure pivot with no model,
 * sitting beside game_release_memory_incompatible and
 * game_release_system_incompatible, which are also pure pivots. It is the
 * only one named with a noun; the adjective form wins because it is the
 * majority, matches the sibling _enhanced and _minimum tables, and matches
 * the relation that reads it, GameRelease::emulatorIncompatibles().
 *
 * game_release_tos_version_incompatibilities keeps its plural -- Rule 2
 * says a table with a model is plural, and this one has
 * GameReleaseTosVersionIncompatibility. What it loses is `version`, which
 * names nothing: the parent table is `tos` and the column is `tos_id`.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 8, and in
 * particular "The stranded TOS constraint, and why this unit closes it" for
 * the hand-written statements below -- the one identifier in the whole
 * campaign TableRenamer cannot reach, because its rewrite rule matches only
 * names beginning with the table's *current* name, and this constraint
 * begins with the singular stem of a table two renames dead.
 */
return new class extends Migration
{
    private const TABLES = [
        'game_release_emulator_incompatibility'      => 'game_release_emulator_incompatible',
        'game_release_tos_version_incompatibilities' => 'game_release_tos_incompatibilities',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        // SQLite names neither indexes nor constraints after the table --
        // the same guard TableRenamer applies to its own raw statements --
        // so there is nothing to rewrite there.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
            DROP FOREIGN KEY `game_release_tos_version_incompatibility_game_release_id_foreign`');
        DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
            ADD CONSTRAINT `game_release_tos_incompatibilities_game_release_id_foreign`
            FOREIGN KEY (`game_release_id`) REFERENCES `game_releases` (`id`)
            ON DELETE CASCADE ON UPDATE RESTRICT');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
                DROP FOREIGN KEY `game_release_tos_incompatibilities_game_release_id_foreign`');
            DB::statement('ALTER TABLE `game_release_tos_incompatibilities`
                ADD CONSTRAINT `game_release_tos_version_incompatibility_game_release_id_foreign`
                FOREIGN KEY (`game_release_id`) REFERENCES `game_releases` (`id`)
                ON DELETE CASCADE ON UPDATE RESTRICT');
        }

        TableRenamer::reverse(self::TABLES);
    }
};
