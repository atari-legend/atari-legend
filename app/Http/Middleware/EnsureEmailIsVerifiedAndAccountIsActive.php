<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

/**
 * Checks that an account has its emails verified (built-in Laravel
 * 'email_verified_at' column) and that a user is active (legacy Atari Legend
 * 'inactive' column).
 */
class EnsureEmailIsVerifiedAndAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (
            $request->user() &&
            ($request->user() instanceof MustVerifyEmail &&
                (! $request->user()->hasVerifiedEmail()) || $request->user()->inactive === User::INACTIVE)
        ) {
            return $request->expectsJson()
                ? abort(403, 'Your email address is not verified.')
                : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
        }

        return $next($request);
    }
}
