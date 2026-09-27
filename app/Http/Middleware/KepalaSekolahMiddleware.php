<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KepalaSekolahMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->hasRole('kepala_sekolah')) {
            return redirect()->route('login')->with('error', 'Akses ditolak. Anda bukan Kepala Sekolah.');
        }

        return $next($request);
    }
}
