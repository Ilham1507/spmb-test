<?php

namespace App\Http\Middleware;

use App\Support\PendaftarSetup;
use App\Support\RegistrationFee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationFeePaid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $pendaftar = PendaftarSetup::getOrCreateFor($user);
        // The same authenticated User instance is reused by the controllers
        // later in this request. Keep its relation fresh so every Save &
        // Continue action writes to the registration just prepared above,
        // including newly created accounts.
        $user->setRelation('pendaftar', $pendaftar);
        $tagihan = RegistrationFee::ensureBill($pendaftar);

        if (RegistrationFee::isPaid($tagihan)) {
            return $next($request);
        }

        $message = RegistrationFee::hasPendingVerification($tagihan)
            ? 'Bukti pembayaran formulir kamu sudah dikirim. Formulir akan terbuka setelah pembayaran diverifikasi panitia.'
            : 'Silakan bayar uang formulir pendaftaran terlebih dahulu. Setelah pembayaran diverifikasi panitia, formulir pendaftaran akan terbuka.';

        return redirect()
            ->route('peserta.pembayaran')
            ->with('warning', $message);
    }
}
