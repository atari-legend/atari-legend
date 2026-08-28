<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the duplicate indexes on users.userid.
 *
 * On the production lineage SHOW INDEX FROM users returns three non-unique
 * indexes on the single column userid: userid, userid_2 and userid_3. The
 * suffixed pair are MySQL's auto-generated names from the same index being
 * added twice more; they index the same column in the same order and serve no
 * read the first one does not.
 *
 * One index on userid survives, whatever it is called -- it backs the login
 * lookup, on a table the login path writes to. Which one survives is decided
 * by name order, so the plain `userid` is kept where it exists and the
 * migrate:fresh lineage keeps its `users_userid_index`, which is the only one
 * that lineage has. There is nothing for this migration to do there.
 *
 * See docs/plans/2026-08-26-schema-consistency-sweep.md, Phase 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $duplicates = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'users')
            ->where('COLUMN_NAME', 'userid')
            ->where('INDEX_NAME', '<>', 'PRIMARY')
            ->distinct()
            ->pluck('INDEX_NAME')
            ->sort()
            ->values()
            ->slice(1);

        Schema::table('users', function (Blueprint $table) use ($duplicates) {
            foreach ($duplicates as $index) {
                $table->dropIndex($index);
            }
        });
    }

    /**
     * Deliberately empty, for the reason given on the migration that drops the
     * redundant indexes: these are duplicates of an index that is still there,
     * a migrate:fresh database has only the one, and re-creating them would
     * restore the divergence rather than a capability.
     */
    public function down(): void
    {
    }
};
