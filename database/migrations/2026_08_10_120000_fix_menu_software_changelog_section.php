<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MenuSoftwareController wrote its changelog entries under the misspelt
 * section 'Menu Softwre'. The controller has been corrected, but every entry
 * written before that still carries the typo, and the changelog page lists
 * sections straight from the column - so the two spellings appeared as two
 * separate sections.
 *
 * AdminStatisticsHelper used to paper over this with an alias applied on every
 * read. Correcting the rows once removes the need for that.
 */
return new class extends Migration
{
    const TYPO = 'Menu Softwre';

    const CORRECT = 'Menu Software';

    public function up(): void
    {
        DB::table('change_log')
            ->where('section', self::TYPO)
            ->update(['section' => self::CORRECT]);
    }

    /**
     * Deliberately not reversible: the correct spelling was also written by
     * hand before now, so rolling back would relabel entries that never had
     * the typo.
     */
    public function down(): void
    {
        // No down migration
    }
};
