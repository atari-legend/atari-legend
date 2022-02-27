<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\Interview;
use App\Models\Review;
use App\Scopes\NonDraftScope;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to add a scope for various content to not show
 * draft items.
 */
class AddNonDraftScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        Article::addGlobalScope(new NonDraftScope());
        Interview::addGlobalScope(new NonDraftScope());
        Review::addGlobalScope(new NonDraftScope());

        return $next($request);
    }
}
