<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('layouts.pagination.bootstrap-5');
        Paginator::defaultSimpleView('layouts.pagination.simple-bootstrap-5');

        Blade::if('contributor', function () {
            return Auth::user() !== null && Auth::user()->permission === User::PERMISSION_ADMIN;
        });

        Blade::directive('activeroute', function ($expression) {
            return "<?php echo Request::routeIs($expression) ? 'active' : ''; ?>";
        });

        Blade::directive('collapsedroute', function ($expression) {
            return "<?php echo Request::routeIs($expression) ? '' : 'collapsed'; ?>";
        });

        Blade::directive('showroute', function ($expression) {
            return "<?php echo Request::routeIs($expression) ? 'show' : ''; ?>";
        });
    }
}
