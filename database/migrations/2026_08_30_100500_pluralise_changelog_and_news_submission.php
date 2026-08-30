<?php

use Database\Support\TableRenamer;
use Illuminate\Database\Migrations\Migration;

/**
 * Pluralise change_log and news_submission.
 *
 * Neither is the target of a foreign key, so each rename touches only its own
 * table. change_log is the largest table this campaign moves -- 61,796 rows,
 * measured 2026-08-30 -- and RENAME TABLE is a metadata operation, so the size
 * costs nothing.
 *
 * Str::plural('Changelog') is 'Changelogs', which is why the target is
 * `changelogs` and not `change_logs`: the class is one word, and the model is
 * what the name has to agree with.
 *
 * Last unit of docs/plans/2026-08-29-plural-table-rename.md, and the last two
 * deletable `protected $table` overrides in app/Models. See
 * Database\Support\TableRenamer for what a rename does beyond Schema::rename.
 */
return new class extends Migration
{
    private const TABLES = [
        'change_log'       => 'changelogs',
        'news_submission'  => 'news_submissions',
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
