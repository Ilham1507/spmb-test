<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\KontakPendaftar;
use App\Support\FormFieldCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class KontakController extends Controller
{
    public function index()
    {
        $pendaftar = Auth::user()->pendaftar;
        $fields = FormFieldCatalog::groups()['Kontak'] ?? [];
        $enabledFields = collect($fields)->filter(fn ($label, $key) => FormFieldCatalog::isEnabled($key));

        return view('peserta.kontak.index', compact('pendaftar', 'enabledFields'));
    }

    public function store(Request $request)
    {
        $emailKey = collect(FormFieldCatalog::groups()['Kontak'] ?? [])
            ->search(fn ($label) => strtolower($label) === 'email');
        $rules = [];
        if ($emailKey !== false && FormFieldCatalog::isEnabled($emailKey)) {
            $rules['email'] = FormFieldCatalog::isRequired($emailKey)
                ? 'required|email|max:150'
                : 'nullable|email|max:150';
        }
        $request->validate($rules, ['email.required' => 'Email wajib diisi sebelum melanjutkan.']);

        $data = [];
        if (FormFieldCatalog::isEnabled('no_handphone')) {
            $data['phone'] = Auth::user()->phone;
        }
        if ($emailKey !== false && FormFieldCatalog::isEnabled($emailKey)) {
            $data['email'] = strtolower(trim((string) $request->input('email')));
        }
        $pendaftar = Auth::user()->pendaftar;
        $contact = KontakPendaftar::firstOrNew(['applicant_id' => $pendaftar->id]);
        $emailChanged = filled($data['email'] ?? null) && $contact->email !== $data['email'];

        foreach ($data as $key => $value) {
            $contact->{$key} = $value;
        }
        $contact->save();

        if (filled($data['email'] ?? null) && ($emailChanged || ! $contact->email_verified_at)) {
            $contact->update([
                'email_verified_at' => null,
                'email_verification_token' => Str::random(64),
                'email_verification_expires_at' => now()->addMinutes(5),
            ]);

            if ($request->input('action') !== 'send_verification') {
                return back()->withInput()->with('warning', 'Tekan Kirim verifikasi email, lalu buka tautan yang masuk ke email Anda.');
            }

            if (config('mail.default') === 'log') {
                return back()->withInput()->with('warning', 'Verifikasi email belum dapat dikirim karena layanan email sekolah belum dihubungkan.');
            }

            $verificationUrl = route('peserta.kontak.verify-email', ['token' => $contact->email_verification_token]);

            try {
                Mail::send('emails.verifikasi-email', [
                    'name' => $pendaftar->biodata?->full_name ?: 'Calon Peserta Didik',
                    'registrationNumber' => $pendaftar->registration_number,
                    'verificationUrl' => $verificationUrl,
                ], fn ($message) => $message
                    ->to($contact->email)
                    ->subject('Verifikasi Email — SPMB SMK Muhammadiyah 4 Cileungsi')
                );
            } catch (\Throwable $exception) {
                Log::warning('Pengiriman verifikasi email pendaftar gagal.', ['applicant_id' => $pendaftar->id, 'error' => $exception->getMessage()]);

                return back()->withInput()->with('error', 'Tautan belum dapat dikirim karena layanan email SPMB sedang belum terhubung. Alamat email Anda sudah tersimpan; silakan hubungi panitia atau coba lagi setelah layanan email diperbaiki.');
            }

            return back()->with('success', 'Tautan verifikasi sudah dikirim dan berlaku selama 5 menit. Buka email tersebut untuk melanjutkan ke dokumen.');
        }

        return $this->redirectAfterParticipantSave($request, 'peserta.dokumen', 'Data kontak berhasil disimpan.', 'peserta.dokumen');
    }

    public function verifyEmail(string $token)
    {
        $contact = KontakPendaftar::where('email_verification_token', $token)->firstOrFail();

        if (! $contact->email_verification_expires_at || $contact->email_verification_expires_at->isPast()) {
            $contact->update([
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);

            return redirect()->route('peserta.kontak')
                ->with('warning', 'Tautan verifikasi sudah kedaluwarsa. Kirim tautan baru dari halaman Data Kontak.');
        }

        $contact->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        return redirect()->route('peserta.kontak')
            ->with('success', 'Email berhasil diverifikasi. Anda dapat melanjutkan ke dokumen.');
    }
}
