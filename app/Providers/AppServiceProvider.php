<?php

namespace App\Providers;

use App\Helpers\GameHelper;
use App\Helpers\GameVoteHelper;
use App\Helpers\Helper;
use App\Helpers\MenuHelper;
use App\Helpers\ReleaseDescriptionHelper;
use App\Models\User;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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

        Route::pattern('id', '[0-9]+');

        $loader = AliasLoader::getInstance();
        $loader->alias('Helper', Helper::class);
        $loader->alias('GameHelper', GameHelper::class);
        $loader->alias('GameVoteHelper', GameVoteHelper::class);
        $loader->alias('MenuHelper', MenuHelper::class);
        $loader->alias('ReleaseDescriptionHelper', ReleaseDescriptionHelper::class);
        $loader->alias('Image', \Intervention\Image\Facades\Image::class);
    }
}
