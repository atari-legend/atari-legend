<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise the three website tables.
 *
 * website and website_category are foreign-key parents -- website_category_cross
 * carries one constraint to each -- so InnoDB rewrites the cross table's
 * referenced names and it is not touched here. website_validate holds no rows
 * and is the target of no constraint; it moves for the override alone.
 *
 * See docs/plans/2026-08-29-plural-table-rename.md, Unit 5, and
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'website'          => 'websites',
        'website_category' => 'website_categories',
        'website_validate' => 'website_validates',
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
