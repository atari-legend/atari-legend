<?php

namespace Database\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert a column holding a unix timestamp -- as an int or as a string of
 * digits -- into a native DATETIME under a new name, and back.
 *
 * Every column docs/plans/2026-09-06-timestamp-type-consistency.md converts
 * also changes name, so no intermediate column is needed: up() adds the
 * target as a nullable DATETIME, backfills it from the source, drops the
 * source, and only then tightens the target to NOT NULL. down() is the same
 * four steps in reverse, rebuilding the source from the type and length the
 * caller passes and restoring its default.
 *
 * The two backfills are the only driver-specific statements, branched on
 * 'not sqlite' rather than '=== mysql' -- this connection's driver name is
 * 'mariadb', the same distinction TableRenamer documents and for the same
 * reason. The SQLite branch runs against tables that migrate:fresh leaves
 * empty, so it exists to keep up() and down() symmetric on both engines
 * rather than to move rows.
 *
 * Both directions take the source column's type, and up() uses it for one
 * thing only: NULLIF(column, ''), which maps an empty string to NULL, is
 * wrapped round a varchar source and not round an int one. The varchar
 * sources need it -- 20 users.join_date and 9 users.last_visit rows hold ''.
 * An int source must not have it: comparing an int column to '' is a
 * truncated-value error under this connection's STRICT_TRANS_TABLES, not the
 * no-op it looks like -- SQLSTATE[22007], "Truncated incorrect DECIMAL
 * value: ''".
 *
 * Both directions assume the MariaDB session timezone matches PHP's, since
 * FROM_UNIXTIME()/UNIX_TIMESTAMP() read the session zone and every reader
 * these columns have reads PHP's. Both are UTC on this stack.
 *
 * A converted column moves to the end of its table, in both directions, so a
 * SHOW CREATE TABLE diff across the migration is not empty even though every
 * value round-trips.
 */
class UnixTimestampColumnConverter
{
    /**
     * Replace $from with a DATETIME called $to, carrying the same moments.
     */
    public static function up(string $table, string $from, string $to, string $sourceType, bool $nullable): void
    {
        Schema::table($table, fn (Blueprint $t) => $t->dateTime($to)->nullable());

        $source = $sourceType === 'string' ? "NULLIF(`{$from}`, '')" : "`{$from}`";

        DB::statement(
            DB::connection()->getDriverName() === 'sqlite'
                ? "UPDATE `{$table}` SET `{$to}` = datetime({$source}, 'unixepoch') WHERE `{$from}` IS NOT NULL"
                : "UPDATE `{$table}` SET `{$to}` = FROM_UNIXTIME({$source}) WHERE `{$from}` IS NOT NULL"
        );

        Schema::table($table, fn (Blueprint $t) => $t->dropColumn($from));

        if (! $nullable) {
            Schema::table($table, fn (Blueprint $t) => $t->dateTime($to)->nullable(false)->change());
        }
    }

    /**
     * Replace the DATETIME $to with $from, back in its original type.
     */
    public static function down(
        string $table,
        string $to,
        string $from,
        string $sourceType,
        ?int $length,
        bool $nullable,
        mixed $default = null
    ): void {
        Schema::table($table, fn (Blueprint $t) => self::source($t, $from, $sourceType, $length)->nullable());

        DB::statement(
            DB::connection()->getDriverName() === 'sqlite'
                ? "UPDATE `{$table}` SET `{$from}` = strftime('%s', `{$to}`) WHERE `{$to}` IS NOT NULL"
                : "UPDATE `{$table}` SET `{$from}` = UNIX_TIMESTAMP(`{$to}`) WHERE `{$to}` IS NOT NULL"
        );

        Schema::table($table, fn (Blueprint $t) => $t->dropColumn($to));

        if (! $nullable || $default !== null) {
            Schema::table($table, function (Blueprint $t) use ($from, $sourceType, $length, $nullable, $default) {
                $column = self::source($t, $from, $sourceType, $length)->nullable($nullable);

                if ($default !== null) {
                    $column->default($default);
                }

                $column->change();
            });
        }
    }

    private static function source(Blueprint $table, string $column, string $type, ?int $length)
    {
        return $type === 'string'
            ? $table->string($column, $length)
            : $table->integer($column);
    }
}
