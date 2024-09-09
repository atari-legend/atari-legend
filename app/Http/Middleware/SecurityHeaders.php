<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

/**
 * Output security-related response headers.
 */
class SecurityHeaders
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
        $viteDevHost = '';
        if (Vite::isRunningHot()) {
            $viteDevHost = 'ws://localhost:5173 http://localhost:5173';
        }

        $csp = collect([
            "default-src 'self' {$viteDevHost} matomo.atarilegend.com hcaptcha.com *.hcaptcha.com 'unsafe-inline' 'unsafe-eval' data: blob:",
            "img-src 'self' data: blob: {$viteDevHost} *.ytimg.com archive.org *.archive.org",
            'frame-src *.hcaptcha.com www.youtube-nocookie.com',
        ]);

        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', $csp->join('; '));

        return $response;
    }
}
