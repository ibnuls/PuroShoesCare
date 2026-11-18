<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivacyHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($request->is('track*', 'staff*', 'livewire*', 'media*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
