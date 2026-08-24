<?php

/**
 * Generated-SQL snapshot, companion to 2026-08-23-foreign-key-rename.md.
 *
 * The campaign's central claim is that Phase A deletes seventy-odd explicit
 * key arguments without changing one character of SQL, and that the
 * Release -> GameRelease rename is likewise a no-op. That claim is only worth
 * as much as the check behind it, so this script produces the check: it boots
 * the application, reflects every relation on every model -- morph relations
 * included, which the audit script skips because they have no foreign key to
 * converge on -- and prints the SQL each one generates.
 *
 * Snapshot before the edit, snapshot after, diff. It is the gate that caught
 * the three Pivot relations whose arguments the audit had wrongly called
 * redundant; a green suite did not.
 *
 *   docker compose run --rm --no-deps php \
 *     php /var/www/html/docs/plans/2026-08-23-foreign-key-rename-sql.php > sql.before
 *
 * Each line is `Class::method()<TAB>sql`. Across a *class rename* the labels
 * necessarily move, so compare the SQL alone -- table and column names carry no
 * class name, so a pure rename leaves this half untouched:
 *
 *   cut -f2 sql.before | sort > a; cut -f2 sql.after | sort > b; diff a b
 */

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Eloquent\Relations\Relation;

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

        printf(
            "%s::%s()\t%s\n",
            class_basename($class),
            $method->name,
            $relation->getQuery()->toSql()
        );
    }
}
