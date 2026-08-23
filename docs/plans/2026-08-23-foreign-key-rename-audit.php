<?php

/**
 * Relationship key audit, companion to 2026-08-23-foreign-key-rename.md.
 *
 * Asks Laravel, rather than grep, which explicit foreign key arguments in
 * app/Models/ are redundant: it reflects every relation method, calls it, and
 * compares the relation's real foreign key against the key Eloquent would have
 * derived by default. Counting "->hasMany(" occurrences gets a different and
 * wrong answer, because redundancy depends on the relation type, the method
 * name, the declaring class name and the related model's key name at once.
 *
 * This lives in docs/plans/ rather than app/Console/Commands/ because it is
 * evidence for a proposal, not application code. If the campaign is approved it
 * should become `artisan al:audit-relationship-keys`, so that Phase A's progress
 * is measurable and Phase C's pull requests can be checked against it.
 *
 *   docker compose run --rm --no-deps php \
 *     php /var/www/html/docs/plans/2026-08-23-foreign-key-rename-audit.php
 *
 * Pass `pivots` to print the pivot table Eloquent resolves for every
 * belongsToMany instead. Phase A deletes key arguments but must keep pivot
 * table arguments, and an over-deletion is invisible until the relation is
 * exercised -- Crew::menuSets() and MenuSet::crews() already pass null and
 * rely on the derived name, so the derive path is live in this codebase.
 * Snapshot before editing, snapshot after, diff. Raised by OpenCode.
 *
 *   ... 2026-08-23-foreign-key-rename-audit.php pivots > /tmp/pivots.before
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/** Does the declaration actually pass a key argument, or only a pivot table? */
function passesKeyArgument(ReflectionMethod $method, bool $isBelongsToMany): bool
{
    $lines = file($method->getFileName());
    $body  = implode('', array_slice(
        $lines,
        $method->getStartLine() - 1,
        $method->getEndLine() - $method->getStartLine() + 1
    ));

    if (! preg_match('/(belongsToMany|hasMany|hasOne|belongsTo)\(\s*[A-Za-z]+::class\s*(,[^)]*)?\)/s', $body, $m)) {
        return false;
    }

    $argc = isset($m[2]) && trim($m[2]) !== '' ? substr_count($m[2], ',') : 0;

    // belongsToMany's second argument is the pivot table, not a key.
    return $isBelongsToMany ? $argc >= 2 : $argc >= 1;
}

if (($argv[1] ?? null) === 'pivots') {
    foreach (glob(base_path('app/Models/*.php')) as $file) {
        $class = 'App\\Models\\' . basename($file, '.php');

        if (! class_exists($class)) {
            continue;
        }

        try {
            $model = new $class;
        } catch (Throwable) {
            continue;
        }

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $class || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $relation = $model->{$method->name}();
            } catch (Throwable) {
                continue;
            }

            if ($relation instanceof BelongsToMany) {
                printf("%-42s %s\n", class_basename($class) . '::' . $method->name . '()', $relation->getTable());
            }
        }
    }

    exit;
}

$redundant = [];
$divergent = [];
$clean     = 0;

foreach (glob(base_path('app/Models/*.php')) as $file) {
    $class = 'App\\Models\\' . basename($file, '.php');

    if (! class_exists($class)) {
        continue;
    }

    try {
        $model = new $class;
    } catch (Throwable) {
        continue;
    }

    foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->class !== $class || $method->getNumberOfParameters() > 0) {
            continue;
        }

        if (str_starts_with($method->name, 'get') && str_ends_with($method->name, 'Attribute')) {
            continue;
        }

        try {
            $relation = $model->{$method->name}();
        } catch (Throwable) {
            continue;
        }

        if (! $relation instanceof Relation) {
            continue;
        }

        $related = $relation->getRelated();

        // The three derivation rules this whole plan turns on. belongsTo reads
        // the *method* name; hasOne/hasMany read the declaring *class* name;
        // belongsToMany reads both class names. None of them read a table name.
        if ($relation instanceof BelongsTo) {
            $actual   = $relation->getForeignKeyName();
            $expected = Str::snake($method->name) . '_' . $related->getKeyName();
        } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
            $actual = $relation->getForeignKeyName();
            // Ask Laravel, do not reimplement the formula. Pivot subclasses
            // override getForeignKey() (AsPivot returns the pivot's runtime
            // foreign key, which is null on a fresh instance), so deriving
            // Str::snake(class_basename).'_'.getKeyName() here reports three
            // arguments as redundant that are in fact load-bearing.
            $expected = $model->getForeignKey();
        } elseif ($relation instanceof BelongsToMany) {
            $actual   = $relation->getForeignPivotKeyName() . '|' . $relation->getRelatedPivotKeyName();
            $expected = Str::snake(class_basename($model)) . '_' . $model->getKeyName() . '|'
                      . Str::snake(class_basename($related)) . '_' . $related->getKeyName();
        } else {
            continue; // morph relations have no foreign key to converge on
        }

        $label = class_basename($class) . '::' . $method->name . '()';

        if ($actual !== $expected) {
            $divergent[] = sprintf(
                '  %-38s %-16s actual=%-34s convention=%s',
                $label,
                class_basename($relation),
                $actual,
                $expected
            );
        } elseif (passesKeyArgument($method, $relation instanceof BelongsToMany)) {
            $redundant[] = sprintf('  %s  (%s)', $label, $actual);
        } else {
            $clean++;
        }
    }
}

echo 'REDUNDANT key arguments — deletable today, no schema change: ' . count($redundant) . "\n";
echo implode("\n", $redundant) . "\n\n";
echo 'DIVERGENT from the convention: ' . count($divergent) . "\n";
echo implode("\n", $divergent) . "\n\n";
echo "ALREADY CLEAN — no key argument passed: {$clean}\n";
echo 'TOTAL: ' . (count($redundant) + count($divergent) + $clean) . " relations\n";
