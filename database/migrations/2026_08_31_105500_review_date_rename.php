<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename reviews.review_date to date.
 *
 * The publication date. It stays an int(11) unix timestamp and Review's
 * $casts entry moves with it, keeping the datetime:timestamp cast.
 *
 * Unit 10 of docs/plans/2026-08-31-column-name-consistency.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('review_date', 'date'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->renameColumn('date', 'review_date'));
    }
};
