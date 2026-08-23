<?php

/**
 * Phase A rewriter, companion to 2026-08-23-foreign-key-rename.md.
 *
 * Deletes the explicit relationship key arguments that are character-for-
 * character what Eloquent derives anyway. It exists because the plan's
 * "70 deleted, SQL diff empty" measurement was taken with a tool nobody else
 * could run, which is this campaign's own argument against lists in documents
 * turned back on it.
 *
 *   docker compose run --rm --no-deps php \
 *     php /var/www/html/docs/plans/2026-08-23-foreign-key-rename-rewrite.php --dry-run
 *   ... 2026-08-23-foreign-key-rename-rewrite.php --write
 *
 * It asks Laravel for every default rather than reimplementing the formula --
 * the audit did reimplement it once and reported three Pivot relations
 * as redundant when AsPivot makes their arguments load-bearing.
 *
 * Two rules keep it honest:
 *
 * 1. It strips arguments from the *end* only, and stops at the first one that
 *    is not the default. `hasMany(X::class, 'fk', 'local_key')` with a
 *    non-default local key keeps both, because dropping the key argument alone
 *    would silently move the other. This matters more than it looks: a wrong
 *    localKey changes the *value bound* into the query, not the SQL text, so
 *    the SQL snapshot cannot see it.
 * 2. It never strips an explicit belongsToMany pivot *table* name, only a
 *    literal `null` in that position. Laravel derives the pivot name
 *    alphabetically from the two class names, which is right for
 *    `game_individual` and wrong for `game_release_crew`.
 *
 * Declarations it cannot rewrite (a call split across lines, an argument that
 * is not a literal) are reported and left alone, for a person to do by hand.
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

$write = in_array('--write', $argv, true);

/** Split an argument list on top-level commas, respecting nesting and quotes. */
function splitArguments(string $args): array
{
    $out = [''];
    $depth = 0;
    $quote = null;

    foreach (str_split($args) as $char) {
        if ($quote !== null) {
            $out[count($out) - 1] .= $char;
            if ($char === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"') {
            $quote = $char;
        } elseif ($char === '(' || $char === '[') {
            $depth++;
        } elseif ($char === ')' || $char === ']') {
            $depth--;
        } elseif ($char === ',' && $depth === 0) {
            $out[] = '';
            continue;
        }

        $out[count($out) - 1] .= $char;
    }

    return array_map('trim', $out);
}

$rewritten = 0;
$byHand    = [];

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

    $source  = file_get_contents($file);
    $changed = false;

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

        // The defaults, in declaration order, asked of Laravel rather than
        // rebuilt from the formula. A null entry is an argument this script
        // will not strip whatever it holds.
        if ($relation instanceof BelongsTo) {
            $defaults = [
                Str::snake($method->name) . '_' . $related->getKeyName(),
                $related->getKeyName(),
                $method->name,
            ];
        } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
            $defaults = [$model->getForeignKey(), $model->getKeyName()];
        } elseif ($relation instanceof BelongsToMany) {
            $defaults = [
                null,                       // the pivot table: never stripped unless literal null
                $model->getForeignKey(),
                $related->getForeignKey(),
                $model->getKeyName(),
                $related->getKeyName(),
            ];
        } else {
            continue;
        }

        // Only relations the audit calls *redundant* are in Phase A's scope. A
        // divergent relation can still carry a trailing argument that happens
        // to be the default -- PublisherDeveloper::games() passes 'dev_pub_id'
        // and then 'game_id' -- and stripping that half is a tidy-up this
        // campaign has not argued for, in a relation Phase C or D will revisit.
        if ($relation instanceof BelongsTo) {
            $divergent = $relation->getForeignKeyName() !== $defaults[0];
        } elseif ($relation instanceof BelongsToMany) {
            $divergent = $relation->getForeignPivotKeyName() !== $defaults[1]
                || $relation->getRelatedPivotKeyName() !== $defaults[2];
        } else {
            $divergent = $relation->getForeignKeyName() !== $defaults[0];
        }

        if ($divergent) {
            continue;
        }

        $type = lcfirst(class_basename($relation));
        $body = implode('', array_slice(
            file($file),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        // Find the call and match its parentheses, so that a nested call or a
        // trailing ->withPivot() does not truncate the argument list.
        if (! preg_match('/->' . $type . '\(/i', $body, $m, PREG_OFFSET_CAPTURE)) {
            continue;
        }

        $open   = $m[0][1] + strlen($m[0][0]) - 1;
        $depth  = 0;
        $quote  = null;
        $close  = null;

        for ($i = $open, $len = strlen($body); $i < $len; $i++) {
            $char = $body[$i];

            if ($quote !== null) {
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $quote = $char;
            } elseif ($char === '(') {
                $depth++;
            } elseif ($char === ')' && --$depth === 0) {
                $close = $i;
                break;
            }
        }

        if ($close === null) {
            continue;
        }

        $call = substr($body, $m[0][1], $close - $m[0][1] + 1);
        $args = splitArguments(substr($body, $open + 1, $close - $open - 1));

        if (count($args) < 2) {
            continue;                     // nothing but the class name
        }

        // Strip from the end, stopping at the first argument that is not the
        // default. Anything else would move a key this script did not check.
        $keep = count($args);

        while ($keep > 1) {
            $i        = $keep - 1;
            $actual   = $args[$i];
            $expected = $defaults[$i - 1] ?? null;
            $isNull   = strtolower($actual) === 'null';

            if (! ($isNull || ($expected !== null && $actual === "'{$expected}'"))) {
                break;
            }

            $keep--;
        }

        if ($keep === count($args)) {
            continue;                     // nothing to delete
        }

        $replacement = '->' . $type . '(' . implode(', ', array_slice($args, 0, $keep)) . ')';

        if (str_contains($call, "\n")) {
            $byHand[] = class_basename($class) . '::' . $method->name . '()  (declaration spans lines)';
            continue;
        }

        $count  = 0;
        $source = str_replace($call, $replacement, $source, $count);

        if ($count !== 1) {
            $byHand[] = class_basename($class) . '::' . $method->name . "()  ({$count} matches for the call)";
            continue;
        }

        printf("%-42s %s\n", class_basename($class) . '::' . $method->name . '()', $replacement);
        $rewritten++;
        $changed = true;
    }

    if ($changed && $write) {
        file_put_contents($file, $source);
    }
}

echo "\nRewritten: {$rewritten}\n";

if ($byHand !== []) {
    echo 'By hand: ' . count($byHand) . "\n  " . implode("\n  ", $byHand) . "\n";
}

if (! $write) {
    echo "\nDry run -- pass --write to apply.\n";
}
