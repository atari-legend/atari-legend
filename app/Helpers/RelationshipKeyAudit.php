<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * What foreign key does each relation in app/Models use, and what would
 * Eloquent have derived if it had not been told?
 *
 * The question sounds like one a grep could answer and is not: redundancy
 * depends on the relation type, the method name, the declaring class name and
 * the related model's key name at once. So this reflects every relation,
 * calls it, and compares the key the relation really uses against the key
 * Eloquent would default to.
 *
 * It asks Laravel for the defaults rather than reimplementing the three
 * formulas. That is not fastidiousness: a version of this that did
 * reimplement them reported the three Pivot ::comment() relations as
 * redundant, because AsPivot overrides getForeignKey() and there is no
 * derivable default on a fresh pivot at all. Deleting those arguments would
 * have produced `where `article_comments`.`` is null`.
 *
 * See docs/plans/2026-08-23-foreign-key-rename.md.
 */
class RelationshipKeyAudit
{
    /**
     * @return Collection<int, object{label: string, type: string, actual: string, convention: string, divergent: bool, redundant: bool}>
     */
    public static function relations(): Collection
    {
        return self::each(function (Model $model, ReflectionMethod $method, Relation $relation) {
            $related = $relation->getRelated();

            // belongsTo derives from the *method* name; hasOne and hasMany
            // from the declaring *class* name; belongsToMany from both class
            // names. None of the three reads a table name, which is where
            // every surprise in this campaign came from.
            if ($relation instanceof BelongsTo) {
                $actual     = $relation->getForeignKeyName();
                $convention = Str::snake($method->name) . '_' . $related->getKeyName();
            } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {
                $actual     = $relation->getForeignKeyName();
                $convention = $model->getForeignKey();
            } elseif ($relation instanceof BelongsToMany) {
                $actual     = $relation->getForeignPivotKeyName() . '|' . $relation->getRelatedPivotKeyName();
                $convention = $model->getForeignKey() . '|' . $related->getForeignKey();
            } else {
                return null;              // morph relations have no key to converge on
            }

            return (object) [
                'label'      => class_basename($model) . '::' . $method->name . '()',
                'type'       => class_basename($relation),
                'actual'     => $actual,
                'convention' => $convention,
                'divergent'  => $actual !== $convention,
                'redundant'  => $actual === $convention
                    && self::passesKeyArgument($method, $relation instanceof BelongsToMany),
            ];
        });
    }

    /**
     * The pivot table Eloquent resolves for every belongsToMany, which is not
     * always the one the declaration names: Crew::menuSets() passes none and
     * relies on the alphabetical default. Deleting a key argument one
     * position too far turns game_release_crew into crew_release, and nothing
     * fails until that relation is exercised -- so snapshot this either side
     * of any edit to the arguments.
     *
     * @return Collection<int, object{label: string, table: string}>
     */
    public static function pivotTables(): Collection
    {
        return self::each(function (Model $model, ReflectionMethod $method, Relation $relation) {
            if (! $relation instanceof BelongsToMany) {
                return null;
            }

            return (object) [
                'label' => class_basename($model) . '::' . $method->name . '()',
                'table' => $relation->getTable(),
            ];
        });
    }

    private static function each(callable $callback): Collection
    {
        $rows = collect();

        foreach (glob(app_path('Models/*.php')) as $file) {
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

                $row = $callback($model, $method, $relation);

                if ($row !== null) {
                    $rows->push($row);
                }
            }
        }

        return $rows;
    }

    /** Does the declaration pass a key argument, or only a pivot table? */
    private static function passesKeyArgument(ReflectionMethod $method, bool $isBelongsToMany): bool
    {
        $body = implode('', array_slice(
            file($method->getFileName()),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));

        // Case-insensitive on purpose. Four declarations in app/Models used to
        // spell it `belongstoMany`; PHP method names are case-insensitive so
        // they worked, and a case-sensitive regex here filed two redundant
        // declarations under "already clean".
        if (! preg_match('/(belongsToMany|hasMany|hasOne|belongsTo)\(\s*[A-Za-z]+::class\s*(,[^)]*)?\)/si', $body, $matches)) {
            return false;
        }

        $count = isset($matches[2]) && trim($matches[2]) !== '' ? substr_count($matches[2], ',') : 0;

        // belongsToMany's second argument is the pivot table, not a key.
        return $isBelongsToMany ? $count >= 2 : $count >= 1;
    }
}
