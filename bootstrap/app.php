<?php

use App\Http\Middleware\AddNonDraftScope;
use App\Http\Middleware\Admin;
use App\Http\Middleware\EnsureEmailIsVerifiedAndAccountIsActive;
use App\Http\Middleware\Header;
use App\Http\Middleware\OnlineUsers;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: fn() => Route::middleware('web')
            ->group(base_path('routes/admin.php'))
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(OnlineUsers::class);
        $middleware->append(Header::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'verified'         => EnsureEmailIsVerifiedAndAccountIsActive::class,
            'admin'            => Admin::class,
            'nondraft'         => AddNonDraftScope::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
