<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * individual_nicks becomes individual_nickname.
 *
 * Self-referential, so Rule 1 does not apply -- there is no owner and no
 * attribute, both sides are individuals -- and it stays on the list of
 * pivots the alphabetical rule cannot reach. What moves is the plural and
 * the abbreviation: it was plural where every pivot is singular, and it
 * abbreviated a word the application spells out in full --
 * Individual::nicknames() and the admin labels already say Nicknames.
 *
 * nick_id does not move. It is one of the four role-qualified foreign keys
 * the column-name campaign examined and deliberately left, because
 * individual_id is already taken by the other half of the pivot.
 *
 * Both foreign keys point at individuals, so this migration renames no
 * column -- the only rename in the campaign of which that is true, and the
 * reason it is the one unit whose migration leaves nothing behind at all.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 7.
 */
return new class extends Migration
{
    private const TABLES = ['individual_nicks' => 'individual_nickname'];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);
    }

    public function down(): void
    {
        TableRenamer::reverse(self::TABLES);
    }
};
