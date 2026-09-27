<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Portal pages may contain student and payment data. Do not let the
        // browser retain them in its history after the session is logged out.
        if ($this->isPrivateRequest($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function isPrivateRequest(Request $request): bool
    {
        return $request->user() !== null
            || $request->is([
                'admin', 'admin/*',
                'panitia', 'panitia/*',
                'bendahara', 'bendahara/*',
                'peserta', 'peserta/*',
                'dashboard', 'profile', 'profile/*',
                'logout',
            ]);
    }
}
