<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * Middleware used to populate the random logos for the header.
 */
class Header
{
    public function handle($request, Closure $next)
    {
        $request->attributes->set('logos', $this->getLogos());
        $request->attributes->set('rightLogos', $this->getRightLogos());
        $request->attributes->set('animations', $this->getAnimations());

        return $next($request);
    }

    /**
     * Get all existing logos images.
     */
    private function getLogos()
    {
        return collect(Storage::disk('images')->files('logos/'));
    }

    /**
     * Get all existing right logo images.
     */
    private function getRightLogos()
    {
        return collect(Storage::disk('images')->files())->filter(function ($file) {
            return strpos($file, 'top_right') === 0;
        });
    }

    /**
     * Get all sprite animations.
     */
    private function getAnimations()
    {
        return collect(Storage::disk('images')->files('animations/'));
    }
}
