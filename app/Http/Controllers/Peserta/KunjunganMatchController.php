<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\KunjunganMatchVerification;
use App\Models\KunjunganPendaftar;
use App\Models\Pendaftar;
use App\Services\WhatsappCloudApiService;
use App\Support\FullNameNormalizer;
use App\Support\KunjunganMatcher;
use App\Support\PendaftarSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class KunjunganMatchController extends Controller
{
    public function sendOtp(KunjunganPendaftar $kunjungan, WhatsappCloudApiService $whatsapp): RedirectResponse
    {
        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        $this->ensureCandidate($kunjungan, $pendaftar);

        $code = (string) random_int(100000, 999999);
        KunjunganMatchVerification::updateOrCreate(
            ['applicant_id' => $pendaftar->id, 'visit_id' => $kunjungan->id],
            [
                'status' => 'otp_sent',
                'otp_hash' => Hash::make($code),
                'otp_expires_at' => now()->addMinutes(10),
                'attempts' => 0,
                'verified_at' => null,
            ]
        );

        try {
            $whatsapp->send((string) Auth::user()->phone,
                "Kode verifikasi kunjungan SPMB kamu: {$code}. Kode berlaku 10 menit dan jangan diberikan kepada orang lain."
            );
        } catch (Throwable $exception) {
            Log::warning('OTP pencocokan kunjungan gagal dikirim.', [
                'applicant_id' => $pendaftar->id,
                'visit_id' => $kunjungan->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('warning', 'Kode verifikasi belum dapat dikirim. Pastikan WhatsApp sekolah Business API aktif.');
        }

        return back()
            ->with('visit_otp_visit_id', $kunjungan->id)
            ->with('success', 'Kode verifikasi sudah dikirim ke nomor WhatsApp akun kamu.');
    }

    public function verify(Request $request, KunjunganPendaftar $kunjungan): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ], ['otp.digits' => 'Masukkan kode verifikasi 6 digit.']);

        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        $this->ensureCandidate($kunjungan, $pendaftar);

        $verification = KunjunganMatchVerification::where('applicant_id', $pendaftar->id)
            ->where('visit_id', $kunjungan->id)
            ->first();

        if (! $verification || ! $verification->otp_hash || ! $verification->otp_expires_at) {
            return back()->with('warning', 'Kirim kode verifikasi terlebih dahulu.');
        }
        if ($verification->otp_expires_at->isPast()) {
            return back()->with('warning', 'Kode verifikasi sudah kedaluwarsa. Kirim kode baru.');
        }
        if ($verification->attempts >= 5) {
            return back()->with('warning', 'Percobaan terlalu banyak. Kirim kode baru.');
        }
        if (! Hash::check($validated['otp'], $verification->otp_hash)) {
            $verification->increment('attempts');
            return back()->with('visit_otp_visit_id', $kunjungan->id)
                ->with('warning', 'Kode verifikasi tidak sesuai.');
        }

        DB::transaction(function () use ($kunjungan, $pendaftar, $verification) {
            $visit = KunjunganPendaftar::lockForUpdate()->findOrFail($kunjungan->id);
            if ($visit->applicant_id && $visit->applicant_id !== $pendaftar->id) {
                abort(409, 'Kunjungan sudah dihubungkan ke akun lain.');
            }

            $visit->update(['applicant_id' => $pendaftar->id]);
            $verification->update([
                'status' => 'verified',
                'verified_at' => now(),
                'otp_hash' => null,
                'otp_expires_at' => null,
            ]);
        });

        return back()->with('success', 'Kunjungan berhasil dihubungkan. Nomor WhatsApp akun ini menjadi kontak utama siswa.');
    }

    public function dismiss(KunjunganPendaftar $kunjungan): RedirectResponse
    {
        $pendaftar = PendaftarSetup::getOrCreateFor(Auth::user());
        $this->ensureCandidate($kunjungan, $pendaftar);

        KunjunganMatchVerification::updateOrCreate(
            ['applicant_id' => $pendaftar->id, 'visit_id' => $kunjungan->id],
            ['status' => 'dismissed', 'otp_hash' => null, 'otp_expires_at' => null]
        );

        return back();
    }

    private function ensureCandidate(KunjunganPendaftar $kunjungan, Pendaftar $pendaftar): void
    {
        abort_if($kunjungan->applicant_id && $kunjungan->applicant_id !== $pendaftar->id, 409);
        abort_unless(
            FullNameNormalizer::normalize($kunjungan->full_name)
                === FullNameNormalizer::normalize(Auth::user()->name),
            403
        );

        $candidate = KunjunganMatcher::candidateFor($pendaftar);
        abort_unless($candidate && $candidate->id === $kunjungan->id, 409);
    }
}
