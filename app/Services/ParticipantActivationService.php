<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParticipantActivationService
{
    public function createLink(User $user): string
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->phone],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        return route('password.reset', ['token' => $token, 'phone' => $user->phone]);
    }

    public function send(User $user, WhatsappCloudApiService $whatsapp): void
    {
        $link = $this->createLink($user);
        $template = trim((string) config('services.whatsapp.templates.activation'));

        if ($template !== '') {
            $whatsapp->sendTemplate(
                $user->phone,
                $template,
                [$user->name, $link],
                (string) config('services.whatsapp.template_language', 'id')
            );
            return;
        }

        $whatsapp->send($user->phone, \App\Support\WhatsappGreeting::opening()."\n\n"
            ."Panitia telah membuat akun SPMB untuk {$user->name}. Buat kata sandi melalui tautan berikut:\n{$link}\n\n"
            .'Setelah itu, masuk dan lengkapi data yang masih kosong. Jika tautan tidak dapat dibuka, silakan gunakan halaman login.');
    }
}
