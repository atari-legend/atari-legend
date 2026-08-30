<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise the four company and individual tables.
 *
 * pub_dev and crew are foreign-key parents -- pub_dev of three constraints
 * and crew of five, counting sub_crew twice -- so InnoDB rewrites the
 * children's referenced names and no child table is touched.
 *
 * pub_dev becomes pub_devs, not the publisher_developers the plan's table
 * proposed. Expanding the abbreviation would have replaced the table's words
 * and left pub_dev_id stranded on three tables -- game_developer,
 * game_release and game_release_distributor -- undoing the foreign-key
 * campaign's own dev_pub_id -> pub_dev_id move, and leaving five DECLINED
 * entries standing forever. Keeping the words and moving the class instead,
 * PublisherDeveloper -> PubDev, closes all three columns and the entries with
 * them; the price is an abbreviation in a class name, which is the smaller of
 * the two.
 *
 * See docs/plans/2026-08-29-plural-table-rename.md, Unit 4, and
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'developer_role'  => 'developer_roles',
        'individual_role' => 'individual_roles',
        'pub_dev'         => 'pub_devs',
        'crew'            => 'crews',
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
