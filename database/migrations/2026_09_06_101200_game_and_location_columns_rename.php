<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Five columns across three tables, all plain RENAME COLUMN -- no
 * TableRenamer, since this unit renames no table.
 *
 * games.number_players_on_same_machine and .multiple_machines are one
 * measurement split two ways, spelled asymmetrically: "on same" against
 * "multiple", singular against plural. Dropping the redundant `number_`
 * (the value is a number) and keeping the two halves parallel fixes both.
 *
 * game_vs.amiga_id is a LemonAmiga identifier -- the admin label already
 * says "LemonAmiga ID" -- sitting beside atari_id, which is a real foreign
 * key into games. lemonamiga_id beside lemon64_slug makes the table's two
 * external identifiers a real pair; atari_id does not move, one of the four
 * role-qualified foreign keys the column-name campaign deliberately left.
 *
 * locations.country_iso2 and .country_iso3 are the naming half of the
 * survey's C12: the prefix is a lie on this table, which holds continents
 * as well as countries, and the continent row for Europe carries
 * country_iso2 = 'eu'. The structural half -- continent_code pointing at a
 * parent through a code rather than a foreign key -- is out of scope, a
 * schema change rather than a rename.
 *
 * game_vs.amiga_id sits under a Laravel-*derived* composite index,
 * game_vs_atari_id_lemon64_slug_amiga_id_index -- unlike the legacy
 * column-named indexes elsewhere in this campaign. It is left stranded by
 * the schema consistency sweep's standing decision on index and constraint
 * names, same as everywhere else in this campaign; a later
 * dropIndex(['atari_id', 'lemon64_slug', 'lemonamiga_id']) would derive a
 * name that does not exist and fail with 1091. locations.country_iso2 sits
 * under `continent_code`, a legacy name that already named neither its
 * table nor its column, and is left the same way.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 12.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->renameColumn('number_players_on_same_machine', 'players_same_machine');
            $t->renameColumn('number_players_multiple_machines', 'players_multiple_machines');
        });
        Schema::table('game_vs', fn (Blueprint $t) => $t->renameColumn('amiga_id', 'lemonamiga_id'));
        Schema::table('locations', function (Blueprint $t) {
            $t->renameColumn('country_iso2', 'iso2');
            $t->renameColumn('country_iso3', 'iso3');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $t) {
            $t->renameColumn('iso3', 'country_iso3');
            $t->renameColumn('iso2', 'country_iso2');
        });
        Schema::table('game_vs', fn (Blueprint $t) => $t->renameColumn('lemonamiga_id', 'amiga_id'));
        Schema::table('games', function (Blueprint $t) {
            $t->renameColumn('players_multiple_machines', 'number_players_multiple_machines');
            $t->renameColumn('players_same_machine', 'number_players_on_same_machine');
        });
    }
};
