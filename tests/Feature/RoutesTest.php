<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    /**
     * Routes that are knowingly registered without a controller method.
     *
     * Nothing should be added here without a reason and a plan to remove it.
     */
    private const KNOWN_BROKEN = [
        // The series datatable renders a delete form pointing at this route
        // (resources/views/admin/games/series/datatable_actions.blade.php), so
        // dropping it breaks /admin/games/series. Clicking the button 500s on
        // the live site today. Fix by implementing the method or removing the
        // button - both are behaviour changes, so neither belongs in a route
        // tidy-up.
        'DELETE admin/games/series/{series} (admin.games.series.destroy) -> App\Http\Controllers\Admin\Games\GameSeriesController::destroy()',
    ];

    /**
     * Every registered route must point at a method that exists.
     *
     * An unqualified Route::resource() registers all seven actions whether the
     * controller implements them or not, and the ones it invents answer 500
     * (BadMethodCallException) rather than 404. Add ->only([...]) / ->except([...])
     * listing the actions the controller actually has.
     */
    public function testEveryRouteResolvesToAnExistingControllerMethod(): void
    {
        $broken = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('controller');

            // Closures, and vendor packages we do not control.
            if (! $action || ! str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action);

            if (! str_starts_with($controller, 'App\\')) {
                continue;
            }

            if (! method_exists($controller, $method)) {
                $broken[] = sprintf(
                    '%s %s (%s) -> %s::%s()',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $route->getName() ?? 'unnamed',
                    $controller,
                    $method
                );
            }
        }

        sort($broken);

        $this->assertSame(
            self::KNOWN_BROKEN,
            $broken,
            'Routes registered without a controller method'
        );
    }
}
