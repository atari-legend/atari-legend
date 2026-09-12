<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The string columns that are one past 255, and the four that are the wrong
 * shape entirely.
 *
 * Ten columns are varchar(256), one character past the 255 the rest of the
 * schema uses; the longest value across them is 128. The two on
 * database_changes are left, with the rest of that CPANEL ledger.
 *
 * Four columns holding a short single-line string are declared as a TEXT
 * variant - articles.title and spotlights.link as mediumtext, which is 16 MB.
 * A title and a URL are neither long nor searched as prose, and a TEXT variant
 * cannot carry a plain index. up() counts the rows that would not fit first and
 * fails rather than truncating one.
 *
 * Two columns holding a body of prose are TEXT, which is 65,535 *bytes* on
 * utf8mb4, not characters; interviews.text is at 72% of that ceiling. Every
 * other body column in the schema is mediumtext, and these two join them - the
 * count holds at 35 across this migration, because articles.title and
 * spotlights.link leave in the same breath.
 *
 * Skipped on SQLite, where the suite runs: it enforces neither a varchar length
 * nor a TEXT ceiling, so the change would be a no-op there - one that still
 * rebuilds each table and re-orders the foreign keys it carries.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 5.
 */
return new class extends Migration
{
    /**
     * [table, column, nullable] for the varchar(256) columns.
     */
    private const OVER_255 = [
        ['changelogs', 'action', false],
        ['changelogs', 'section', false],
        ['changelogs', 'section_name', false],
        ['changelogs', 'sub_section', false],
        ['changelogs', 'sub_section_name', false],
        ['engines', 'description', true],
        ['magazine_issues', 'label', true],
        ['media', 'label', true],
        ['programming_languages', 'description', true],
        ['sound_hardware', 'description', true],
    ];

    /**
     * [table, column, nullable, type it had before] for the four short strings
     * declared as a TEXT variant.
     */
    private const OVERSIZED = [
        ['articles', 'title', false, 'mediumText'],
        ['spotlights', 'link', true, 'mediumText'],
        ['magazine_issues', 'archiveorg_url', true, 'text'],
        ['magazine_issues', 'alternate_url', true, 'text'],
    ];

    /**
     * [table, column, nullable] for the two body columns that are still TEXT.
     */
    private const UNDERSIZED = [
        ['interviews', 'text', false],
        ['menu_disks', 'scrolltext', true],
    ];

    public function up(): void
    {
        if ($this->skipped()) {
            return;
        }

        foreach (self::OVERSIZED as [$table, $column]) {
            $over = DB::table($table)->whereRaw("CHAR_LENGTH(`{$column}`) > 255")->count();

            if ($over > 0) {
                throw new RuntimeException("{$table}.{$column} has {$over} rows longer than 255 characters");
            }
        }

        foreach ([...self::OVER_255, ...self::OVERSIZED] as [$table, $column, $nullable]) {
            $this->change($table, fn (Blueprint $t) => $t->string($column, 255)->nullable($nullable));
        }

        foreach (self::UNDERSIZED as [$table, $column, $nullable]) {
            $this->change($table, fn (Blueprint $t) => $t->mediumText($column)->nullable($nullable));
        }
    }

    public function down(): void
    {
        if ($this->skipped()) {
            return;
        }

        foreach (self::OVER_255 as [$table, $column, $nullable]) {
            $this->change($table, fn (Blueprint $t) => $t->string($column, 256)->nullable($nullable));
        }

        foreach (self::OVERSIZED as [$table, $column, $nullable, $was]) {
            $this->change($table, fn (Blueprint $t) => $t->{$was}($column)->nullable($nullable));
        }

        foreach (self::UNDERSIZED as [$table, $column, $nullable]) {
            $this->change($table, fn (Blueprint $t) => $t->text($column)->nullable($nullable));
        }
    }

    private function skipped(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    private function change(string $table, callable $column): void
    {
        Schema::table($table, function (Blueprint $t) use ($column) {
            $column($t)->change();
        });
    }
};
