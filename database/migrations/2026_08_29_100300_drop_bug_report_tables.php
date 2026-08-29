<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the bug report pair, a form this application does not have.
 *
 * bug_report holds one row, a report about the pre-Laravel site quoting a
 * games_detail.php URL; bug_report_type holds four lookup rows -- Bug, Layout,
 * General, Suggestion. Neither has a model or a relation, and nothing writes
 * either. The single reader was one count on the admin statistics page, which
 * goes with them: it reported a figure that has been 1 since 2020.
 *
 * bug_report drops first, because bug_report_bug_report_type_id_foreign points
 * from it into bug_report_type and dropping the parent first fails with 1451.
 * Dropping bug_report takes both of its constraints with it, so bug_report_type
 * needs no explicit dropForeign().
 *
 * down() restores structure, never rows, and recreates in the opposite order:
 * bug_report_type first, then bug_report with both SET NULL constraints. The
 * columns come back as they stand today, with id rather than the bug_report_id
 * and bug_report_type_id the schema consistency sweep renamed.
 *
 * See docs/plans/2026-08-28-dead-tables-and-columns.md, unit 4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bug_report');
        Schema::dropIfExists('bug_report_type');
    }

    public function down(): void
    {
        Schema::create('bug_report_type', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('bug_report_type', 128)->nullable();
        });

        Schema::create('bug_report', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('bug_report_type_id')->nullable();
            $table->mediumText('bug_report_text')->nullable();
            $table->integer('bug_report_date')->nullable();
            $table->integer('user_id')->nullable();

            $table->foreign('bug_report_type_id')->references('id')->on('bug_report_type')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
