<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckIfNotSubmitted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pendaftar = Auth::user()?->pendaftar;

        if (
            $pendaftar
            && $pendaftar->registration_status === 'submitted'
            && $pendaftar->correction_status !== 'requested'
        ) {
            return redirect()->route('peserta.review')->with('warning', 'Pendaftaran Anda sudah dikirim. Data tidak dapat diubah lagi.');
        }

        return $next($request);
    }
}
