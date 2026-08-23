<?php

namespace App\Console\Commands;

use App\Helpers\RelationshipKeyAudit;
use Illuminate\Console\Command;

/**
 * Report which relationship key arguments in app/Models are redundant, which
 * diverge from what Eloquent would derive, and which pivot table each
 * belongsToMany resolves to.
 *
 * The foreign key campaign was measured with this: every pull request in it
 * stated the count movement it expected and was checked against the output.
 * RelationshipKeyConventionsTest is what keeps the divergent set from growing
 * once nobody is running this by hand any more.
 */
class AuditRelationshipKeys extends Command
{
    protected $signature = 'al:audit-relationship-keys {--pivots : Print the resolved pivot table of every belongsToMany instead}';

    protected $description = 'Audit the relationship foreign keys in app/Models against Eloquent\'s conventions';

    public function handle(): int
    {
        if ($this->option('pivots')) {
            foreach (RelationshipKeyAudit::pivotTables() as $pivot) {
                $this->line(sprintf('%-42s %s', $pivot->label, $pivot->table));
            }

            return self::SUCCESS;
        }

        $relations = RelationshipKeyAudit::relations();
        $redundant = $relations->where('redundant', true);
        $divergent = $relations->where('divergent', true);

        $this->info('REDUNDANT key arguments -- deletable, no schema change: ' . $redundant->count());
        foreach ($redundant as $relation) {
            $this->line(sprintf('  %s  (%s)', $relation->label, $relation->actual));
        }

        $this->newLine();
        $this->info('DIVERGENT from the convention: ' . $divergent->count());
        foreach ($divergent as $relation) {
            $this->line(sprintf(
                '  %-38s %-16s actual=%-34s convention=%s',
                $relation->label,
                $relation->type,
                $relation->actual,
                $relation->convention
            ));
        }

        $this->newLine();
        $this->info('ALREADY CLEAN -- no key argument passed: '
            . ($relations->count() - $redundant->count() - $divergent->count()));
        $this->info('TOTAL: ' . $relations->count() . ' relations');

        return self::SUCCESS;
    }
}
