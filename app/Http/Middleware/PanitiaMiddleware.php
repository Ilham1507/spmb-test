<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PanitiaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('error', 'Akses ditolak. Anda bukan Panitia.');
        }

        if (auth()->user()->hasRole('panitia') || auth()->user()->hasRole('admin')) {
            return $next($request);
        }

        if ((auth()->user()->hasRole('kepala_sekolah') || auth()->user()->hasRole('bendahara')) && $request->routeIs('panitia.dashboard')) {
            return redirect()->route(auth()->user()->hasRole('bendahara') ? 'bendahara.dashboard' : 'kepala-sekolah.dashboard');
        }

        if (auth()->user()->hasRole('kepala_sekolah') && $request->routeIs('panitia.kunjungan.store')) {
            return $next($request);
        }

        if ((auth()->user()->hasRole('kepala_sekolah') || auth()->user()->hasRole('bendahara')) && $request->isMethodSafe()) {
            return $next($request);
        }

        if (auth()->user()->hasRole('kepala_sekolah')) {
            return redirect()->route('kepala-sekolah.dashboard');
        }

        return redirect()->route('bendahara.dashboard')->with('error', 'Bendahara hanya memiliki akses pemantauan.');
    }
}
