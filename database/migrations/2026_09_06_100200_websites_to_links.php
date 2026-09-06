<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * websites becomes links, and its category and submission satellites move
 * with it.
 *
 * website_categories is a pure parent, like pub_devs -- TableRenamer rewrites
 * nothing for it. websites and website_validates each carry a user_id foreign
 * key of their own; link_category carries both halves of its composite unique
 * index. The rewritten names are recorded in
 * docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 2.
 *
 * link_category.website_category_id has no foreign key of its own (the
 * survey's A6) and renames like a plain column; adding the constraint is
 * category A and out of scope here.
 *
 * See docs/plans/2026-09-02-entity-names-and-pivot-order.md, Unit 2.
 */
return new class extends Migration
{
    private const TABLES = [
        'websites'               => 'links',
        'website_categories'     => 'categories',
        'website_category_cross' => 'link_category',
        'website_validates'      => 'link_submissions',
    ];

    public function up(): void
    {
        TableRenamer::rename(self::TABLES);

        Schema::table('link_category', function (Blueprint $t) {
            $t->renameColumn('website_id', 'link_id');
            $t->renameColumn('website_category_id', 'category_id');
        });
        Schema::table('link_submissions', fn (Blueprint $t) => $t->renameColumn('website_category_id', 'category_id'));
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('count', 'view_count'));
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('rate_number', 'rating_count'));
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('rate_score', 'rating_total'));
    }

    public function down(): void
    {
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('rating_total', 'rate_score'));
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('rating_count', 'rate_number'));
        Schema::table('links', fn (Blueprint $t) => $t->renameColumn('view_count', 'count'));
        Schema::table('link_submissions', fn (Blueprint $t) => $t->renameColumn('category_id', 'website_category_id'));
        Schema::table('link_category', function (Blueprint $t) {
            $t->renameColumn('category_id', 'website_category_id');
            $t->renameColumn('link_id', 'website_id');
        });

        TableRenamer::reverse(self::TABLES);
    }
};
