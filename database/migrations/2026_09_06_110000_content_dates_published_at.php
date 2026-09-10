<?php

use Database\Support\UnixTimestampColumnConverter;
use Illuminate\Database\Migrations\Migration;

/**
 * The four editorial publication dates -- articles, news, interviews and
 * reviews -- from int(11) unix timestamps to DATETIME, renamed published_at.
 *
 * DATETIME rather than DATE: no current reader wants a time of day, but 353
 * news rows and 2 reviews rows carry one, and DATE would truncate them in a
 * way down() could not undo. DATETIME keeps the migration a byte-for-byte
 * round trip, and the admin form gains a datetime-local control to match.
 *
 * published_at rather than created_at: an editor sets the date on the form
 * and can backdate it, and the public pages label it as the item's date.
 *
 * news.date's DEFAULT 0 does not survive -- a DATETIME cannot carry it -- so
 * news.published_at comes out NOT NULL with no default and an insert that
 * omits it now fails under STRICT_TRANS_TABLES instead of storing 0. Every
 * writer sets it. down() restores the default.
 *
 * See docs/plans/2026-09-06-timestamp-type-consistency.md, Unit 1.
 */
return new class extends Migration
{
    private const TABLES = ['articles', 'news', 'interviews', 'reviews'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            UnixTimestampColumnConverter::up($table, 'date', 'published_at', sourceType: 'integer', nullable: false);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            UnixTimestampColumnConverter::down(
                $table,
                'published_at',
                'date',
                sourceType: 'integer',
                length: null,
                nullable: false,
                default: $table === 'news' ? 0 : null,
            );
        }
    }
};
