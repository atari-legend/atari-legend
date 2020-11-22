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
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::defaultView('layouts.pagination.bootstrap-5');
        Paginator::defaultSimpleView('layouts.pagination.simple-bootstrap-5');

        Blade::if('contributor', function () {
            return Auth::user() !== null && Auth::user()->permission === User::PERMISSION_ADMIN;
        });

        Blade::directive('activeroute', function($expression) {
            return "<?php echo Request::routeIs('$expression') ? 'active' : ''; ?>";
        });
    }
}
