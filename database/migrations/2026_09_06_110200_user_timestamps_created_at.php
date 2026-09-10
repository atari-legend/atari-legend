<?php

use Database\Support\UnixTimestampColumnConverter;
use Illuminate\Database\Migrations\Migration;

/**
 * The two account timestamps on `users`, both varchar(32) unix timestamps, to
 * DATETIME.
 *
 * join_date is written once, at registration, and is the row's creation time
 * under another name, so it becomes created_at and Eloquent takes it over.
 * last_visit is not a row-lifecycle column, so it keeps its own name and takes
 * the _at suffix the table already uses on email_verified_at.
 *
 * 20 join_date and 9 last_visit rows hold an empty string rather than a
 * timestamp; UnixTimestampColumnConverter's NULLIF turns those into NULL, and
 * both readers already branch on falsiness, so they render the same '-' as
 * before. down() brings them back as NULL rather than '' -- the one value
 * this conversion does not round-trip, recorded rather than fixed.
 *
 * See docs/plans/2026-09-06-timestamp-type-consistency.md, Unit 3.
 */
return new class extends Migration
{
    public function up(): void
    {
        UnixTimestampColumnConverter::up('users', 'join_date', 'created_at', sourceType: 'string', nullable: true);
        UnixTimestampColumnConverter::up('users', 'last_visit', 'last_visit_at', sourceType: 'string', nullable: true);
    }

    public function down(): void
    {
        UnixTimestampColumnConverter::down('users', 'last_visit_at', 'last_visit', 'string', 32, nullable: true);
        UnixTimestampColumnConverter::down('users', 'created_at', 'join_date', 'string', 32, nullable: true);
    }
};
