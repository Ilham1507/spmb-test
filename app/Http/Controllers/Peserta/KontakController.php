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
            $rules['email'] = 'required|email|max:150';
        }
        $request->validate($rules);

        $data = [];
        if (FormFieldCatalog::isEnabled('no_handphone')) {
            $data['phone'] = Auth::user()->phone;
        }
        if ($emailKey !== false && FormFieldCatalog::isEnabled($emailKey)) {
            $data['email'] = filled($request->input('email'))
                ? strtolower(trim((string) $request->input('email')))
                : null;
        }
        $pendaftar = Auth::user()->pendaftar;
        $contact = KontakPendaftar::firstOrNew(['applicant_id' => $pendaftar->id]);
        $emailChanged = array_key_exists('email', $data) && $contact->email !== $data['email'];

        foreach ($data as $key => $value) {
            $contact->{$key} = $value;
        }
        $contact->save();

        if (filled($data['email'] ?? null) && ($emailChanged || ! $contact->email_verified_at)) {
            $contact->update([
                'email_verified_at' => null,
                'email_verification_token' => Str::random(64),
                'email_verification_expires_at' => now()->addMinutes(30),
            ]);

            if (config('mail.default') === 'log') {
                return back()->withInput()->with('warning', 'Email sudah tersimpan, tetapi tautan verifikasi belum dapat dikirim. Coba kirim ulang atau hubungi panitia.');
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

                return back()->withInput()->with('error', 'Email wajib sudah tersimpan, tetapi tautan verifikasi belum dapat dikirim. Silakan coba Kirim ulang verifikasi atau hubungi panitia.');
            }

            return back()->with('success', 'Tautan verifikasi dikirim ke email Anda. Buka tautan tersebut sebelum dapat melanjutkan ke tahap berikutnya.');
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

        // Finish the verification response before rendering/sending any PDFs.
        $contactId = $contact->id;
        app()->terminating(function () use ($contactId): void {
            $verifiedContact = KontakPendaftar::find($contactId);
            if ($verifiedContact) {
                app(\App\Services\InvoiceEmailNotifier::class)->sendVerifiedForContact($verifiedContact);
            }
        });

        return redirect()->route('peserta.kontak')
            ->with('success', 'Email berhasil diverifikasi. Anda dapat melanjutkan pendaftaran.');
    }
}
