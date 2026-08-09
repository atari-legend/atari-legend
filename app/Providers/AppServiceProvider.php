<?php

namespace App\Providers;

use App\Helpers\GameHelper;
use App\Helpers\GameVoteHelper;
use App\Helpers\Helper;
use App\Helpers\MenuHelper;
use App\Helpers\ReleaseDescriptionHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
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
        $this->preventSilentAttributeErrors();

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

    /**
     * Fail on attributes that do not exist, instead of silently reading NULL.
     *
     * Without this a column that has been renamed or dropped makes every
     * reference to it - in a controller, a view component or a Blade template -
     * evaluate to NULL, so the page still renders and still returns a 200. That
     * hides schema changes from us and from the test suite.
     *
     * Outside production this throws. In production it only logs, so a stale
     * attribute reference degrades a page rather than breaking it.
     */
    private function preventSilentAttributeErrors(): void
    {
        Model::preventAccessingMissingAttributes();

        if ($this->app->isProduction()) {
            Model::handleMissingAttributeViolationUsing(
                function (Model $model, string $attribute) {
                    Log::warning('Accessed missing attribute', [
                        'model'     => $model::class,
                        'attribute' => $attribute,
                    ]);
                }
            );
        } else {
            // Assigning an attribute that is not fillable is just as silent,
            // but a failed save is riskier to surface on the live site.
            Model::preventSilentlyDiscardingAttributes();
        }
    }
}
