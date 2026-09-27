<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BendaharaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || (!auth()->user()->hasRole('bendahara') && !auth()->user()->hasRole('admin'))) {
            return redirect()->route('login')->with('error', 'Akses ditolak. Anda bukan Bendahara.');
        }

        return $next($request);
    }
}
