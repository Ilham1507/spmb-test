<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerifikasiPerubahanWhatsapp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PhoneChangeVerificationService
{
    public function send(User $user, string $phone, WhatsappCloudApiService $whatsapp): void
    {
        if (VerifikasiPerubahanWhatsapp::where('phone', $phone)->where('user_id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages(['phone' => 'Nomor WhatsApp ini sedang menunggu verifikasi untuk akun lain.']);
        }
        $code = (string) random_int(100000, 999999);
        $whatsapp->send($phone, "Kode verifikasi perubahan nomor WhatsApp SPMB: {$code}. Kode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.");

        VerifikasiPerubahanWhatsapp::updateOrCreate(
            ['user_id' => $user->id],
            ['phone' => $phone, 'code_hash' => Hash::make($code), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'last_sent_at' => now()]
        );
    }
}
