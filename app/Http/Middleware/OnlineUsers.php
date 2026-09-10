<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;

class OnlineUsers
{
    /**
     * Populates the request with the list of currently online users,
     * and users from the past 24h.
     *
     * Also updates the current user last visit
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $oneMinuteAgo = Carbon::now()->subMinute();
        $onlineUsers = User::where('last_visit_at', '>=', $oneMinuteAgo)
            ->where('inactive', '=', 0)
            ->get();
        $request->attributes->set('onlineUsers', $onlineUsers);

        $oneDayAgo = Carbon::now()->subDay();
        $pastDayUsers = User::where('last_visit_at', '>=', $oneDayAgo)
            ->where('inactive', '=', 0)
            ->get();
        $request->attributes->set('pastDayUsers', $pastDayUsers);

        $response = $next($request);

        if (Auth::check()) {
            Auth::user()->last_visit_at = now();
            // Do not trigger an event otherwise it will be logged in the changelog
            Auth::user()->saveQuietly();
        }

        return $response;
    }
}
