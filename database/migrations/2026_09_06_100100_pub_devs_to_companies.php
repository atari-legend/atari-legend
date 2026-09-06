<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pub_devs becomes companies, the last abbreviation this campaign renames.
 *
 * pub_devs is a pure foreign-key parent -- it carries no index or constraint
 * of its own -- so TableRenamer rewrites nothing here and behaves exactly as
 * Schema::rename would. It is used anyway, so every rename in the campaign
 * reads the same. InnoDB rewrites the three children's referenced table name
 * itself, so none of them is touched by this migration.
 *
 * Unit 1 of docs/plans/2026-09-02-entity-names-and-pivot-order.md.
 */
return new class extends Migration
{
    private const TABLES = ['pub_devs' => 'companies'];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        Schema::table('game_developer', fn (Blueprint $t) => $t->renameColumn('pub_dev_id', 'company_id'));
        Schema::table('game_releases', fn (Blueprint $t) => $t->renameColumn('pub_dev_id', 'company_id'));
        Schema::table('game_release_distributor', fn (Blueprint $t) => $t->renameColumn('pub_dev_id', 'company_id'));
    }

    public function down(): void
    {
        Schema::table('game_release_distributor', fn (Blueprint $t) => $t->renameColumn('company_id', 'pub_dev_id'));
        Schema::table('game_releases', fn (Blueprint $t) => $t->renameColumn('company_id', 'pub_dev_id'));
        Schema::table('game_developer', fn (Blueprint $t) => $t->renameColumn('company_id', 'pub_dev_id'));

        TableRenamer::reverse(self::TABLES);
    }
};
