<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * The two singular tables that hold records rather than a pivot.
 *
 * database_change and game_gallery are not pivots: they hold 267 and 118
 * rows of real records. Keeping a table is not a reason to leave it
 * singular, and Rule 2 says a table with no model is singular only because
 * it is a pivot -- neither of these is.
 *
 * Neither carries an index, a constraint, or a foreign key pointing at it,
 * so TableRenamer rewrites nothing and does exactly what Schema::rename
 * would. It is used for consistency with the other renames.
 *
 * game_gallery.game_description_gallery and .image_ext are not renamed
 * here -- the column-name campaign examined the former and left it with a
 * recorded reason, and the latter is category B (type and spelling drift),
 * out of scope for this plan.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 9.
 */
return new class extends Migration
{
    private const TABLES = [
        'database_change' => 'database_changes',
        'game_gallery'    => 'game_galleries',
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
