<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fold individual_text into individuals and drop it.
 *
 * This pair is not 1:0..1, whatever the hasOne on Individual::text() says.
 * 5,405 individuals share 4,528 text rows: 891 have none, and **fourteen have
 * two**. The FIXME on that relation ("The DB structure actually allows many")
 * was right, and the merge is what finally makes the schema agree with it.
 *
 * The fourteen are harmless, but only because all 28 rows involved are
 * entirely empty - NULL profile, NULL image extension, NULL email. That is a
 * fact about today's data, not a rule, so it is asserted below rather than
 * assumed. The plain duplicate check the other merges use is no good here:
 * duplicates exist and are expected. What must not exist is a duplicate
 * carrying data.
 *
 * The guard alone is not enough either. A scalar correlated subquery may
 * return at most one row, so the template's backfill dies on each of the
 * fourteen with "ERROR 1242: Subquery returns more than 1 row" - after the
 * columns have been added, with a message that says nothing about duplicates.
 * Hence ORDER BY t.id LIMIT 1. The guard is what makes the LIMIT safe rather
 * than arbitrary: with "no duplicate carries data" already asserted, any row
 * it skips is provably all-NULL and identical to the one it keeps.
 *
 * The columns land nullable, so there is no nullable(false)->change() step and
 * no integrity assertion riding on it: the explicit $expected/$moved count is
 * all this migration has.
 *
 * Two drifts in the down(), both deliberate and neither recoverable by rolling
 * forward again:
 *
 * - It recreates one row per individual, 5,405 of them, where production has
 *   4,528. The 877 extra are all-NULL rows of exactly the kind production is
 *   already 94% full of, and the fourteen duplicate pairs collapse to one
 *   each. Filtering to individuals with any data would drift the other way and
 *   much further, dropping 4,237 empty rows. Nothing reads an empty child row
 *   - AdminStatisticsHelper::countWithText() counts non-empty values only - so
 *   no figure on the site moves either way.
 * - Every recreated column is ->nullable(). Not one individual in production
 *   has ind_profile, ind_imgext and ind_email all non-NULL, so a NOT NULL
 *   down() could not insert a single row: it would abort on the first one.
 *
 * See docs/plans/2026-08-24-main-text-table-merge.md, Phase 5.
 */
return new class extends Migration
{
    private const COLUMNS = ['ind_profile', 'ind_imgext', 'ind_email'];

    public function up(): void
    {
        $carryingData = DB::table('individual_text')
            ->select('individual_id')
            ->groupBy('individual_id')
            ->havingRaw('COUNT(*) > 1 AND (COUNT(ind_profile) > 0 OR COUNT(ind_imgext) > 0 OR COUNT(ind_email) > 0)')
            ->count();

        if ($carryingData > 0) {
            throw new RuntimeException(
                "individual_text holds {$carryingData} individuals with more than one row carrying data; "
                . 'the backfill below would keep only one of them.'
            );
        }

        $expected = DB::table('individual_text')->distinct()->count('individual_id');

        Schema::table('individuals', function (Blueprint $t) {
            $t->mediumText('ind_profile')->nullable()->after('ind_name');
            $t->string('ind_imgext', 50)->nullable()->after('ind_profile');
            $t->string('ind_email', 50)->nullable()->after('ind_imgext');
        });

        foreach (self::COLUMNS as $column) {
            DB::table('individuals')->update([
                $column => DB::raw(
                    "(SELECT t.`{$column}` FROM `individual_text` t "
                    . 'WHERE t.`individual_id` = `individuals`.`id` ORDER BY t.`id` LIMIT 1)'
                ),
            ]);
        }

        // Counted over the individuals that had a row at all, since 891 had
        // none and the columns are nullable: what is being checked is that
        // every child row found a home, not that every parent gained data.
        $moved = DB::table('individuals')
            ->whereIn('id', DB::table('individual_text')->distinct()->pluck('individual_id'))
            ->count();

        if ($moved !== $expected) {
            throw new RuntimeException("Backfilled {$moved} of {$expected} individual_text rows; refusing to drop the table.");
        }

        Schema::drop('individual_text');
    }

    public function down(): void
    {
        Schema::create('individual_text', function (Blueprint $t) {
            // integer(.., true) and not increments(): the legacy column is a
            // signed int(11) and increments() would recreate it unsigned.
            $t->integer('id', true);
            $t->integer('individual_id')->nullable();
            $t->mediumText('ind_profile')->nullable();
            $t->string('ind_imgext', 50)->nullable();
            $t->string('ind_email', 50)->nullable();
            $t->foreign('individual_id')->references('id')->on('individuals')->cascadeOnDelete();
        });

        DB::table('individual_text')->insertUsing(
            ['individual_id', ...self::COLUMNS],
            DB::table('individuals')->select('id', ...self::COLUMNS)
        );

        Schema::table('individuals', function (Blueprint $t) {
            $t->dropColumn(self::COLUMNS);
        });
    }
};
