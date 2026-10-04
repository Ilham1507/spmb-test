<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsappCloudApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, WhatsappCloudApiService $whatsapp): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $request->validate(['phone' => ['required', 'regex:/^08[0-9]{8,13}$/']], [
            'phone.regex' => 'Masukkan nomor WhatsApp dengan format 08xxxxxxxxxx.',
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (! $user) {
            return back()->withInput()->withErrors([
                'phone' => 'Nomor WhatsApp ini belum terdaftar. Periksa kembali atau buat akun baru.',
            ]);
        }

        if (config('services.whatsapp.use_otp', true)) {
            try {
                $code = app(\App\Services\ParticipantActivationService::class)->createCode($user);
                $whatsapp->sendAuthentication($user->phone, $code);
            } catch (\Throwable $exception) {
                Log::warning('Kode verifikasi WhatsApp belum dapat dikirim.', ['user_id' => $user->id]);
                return back()->withInput()->withErrors(['phone' => 'Kode belum dapat dikirim. Silakan coba kembali atau hubungi panitia.']);
            }
            return redirect()->route('password.reset', ['token' => 'kode', 'phone' => $user->phone])
                ->with('status', 'Kode verifikasi dikirim melalui WhatsApp dan berlaku 5 menit.');
        }

        $plainToken = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->phone],
            ['token' => Hash::make($plainToken), 'created_at' => now()]
        );
        $url = route('password.reset', ['token' => $plainToken, 'phone' => $request->phone]);
        $message = \App\Support\WhatsappGreeting::opening()."\n\n"
            ."{$user->name}, buat kata sandi baru akun SPMB melalui tautan berikut:\n{$url}\n\n"
            .'Jika kamu tidak meminta perubahan kata sandi, abaikan pesan ini.';

        try {
            $template = trim((string) config('services.whatsapp.templates.password_reset'));
            if ($template !== '') {
                $whatsapp->sendTemplate(
                    $request->phone,
                    $template,
                    [$user->name, $url],
                    (string) config('services.whatsapp.template_language', 'id')
                );
            } else {
                $whatsapp->send($request->phone, $message);
            }
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengirim reset kata sandi melalui WhatsApp Business API', ['user_id' => $user->id, 'error' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['phone' => 'Tautan belum dapat dikirim. Periksa kredensial WhatsApp Business API dan template pesan sekolah.']);
        }

        return back()->with('status', 'Tautan membuat kata sandi baru telah dikirim melalui WhatsApp.');
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
