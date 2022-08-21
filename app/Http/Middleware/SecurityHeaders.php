<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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
        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');

        $csp = collect([
            "default-src 'self' matomo.atarilegend.com hcaptcha.com *.hcaptcha.com 'unsafe-inline' 'unsafe-eval' data:",
            "img-src 'self' data: *.ytimg.com archive.org *.archive.org",
            'frame-src *.hcaptcha.com www.youtube-nocookie.com',
        ]);

        $response->headers->set(
            'Content-Security-Policy',
            $csp->join('; ')
        );

        return $response;
    }
}
