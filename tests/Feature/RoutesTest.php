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
    private const KNOWN_BROKEN = [];

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
