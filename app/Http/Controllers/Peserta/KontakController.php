<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\KontakPendaftar;
use App\Support\FormFieldCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $data = [];
        if (FormFieldCatalog::isEnabled('no_handphone')) {
            $data['phone'] = Auth::user()->phone;
        }
        $pendaftar = Auth::user()->pendaftar;
        $contact = KontakPendaftar::firstOrNew(['applicant_id' => $pendaftar->id]);

        foreach ($data as $key => $value) {
            $contact->{$key} = $value;
        }
        $contact->save();

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
