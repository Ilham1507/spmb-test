<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\BiodataPendaftar;
use App\Models\KontakPendaftar;
use App\Models\VerifikasiPerubahanWhatsapp;
use App\Services\WhatsappCloudApiService;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'phoneVerification' => $request->user()->phoneChangeVerification,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, WhatsappCloudApiService $whatsapp): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $phoneChanged = $validated['phone'] !== $user->phone;

        $user->update(['name' => $validated['name']]);

        if (! $phoneChanged) {
            $this->syncApplicantContact($user);
            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        if (VerifikasiPerubahanWhatsapp::where('phone', $validated['phone'])->where('user_id', '!=', $user->id)->exists()) {
            return Redirect::route('profile.edit')->withErrors(['phone' => 'Nomor WhatsApp ini sedang menunggu verifikasi untuk akun lain.']);
        }

        $code = (string) random_int(100000, 999999);
        try {
            $whatsapp->send($validated['phone'], "Kode verifikasi perubahan nomor WhatsApp SPMB: {$code}. Kode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.");
        } catch (Throwable $exception) {
            report($exception);
            return Redirect::route('profile.edit')->withErrors(['phone' => 'Kode verifikasi belum dapat dikirim ke nomor baru. Nomor lama tetap digunakan.']);
        }

        VerifikasiPerubahanWhatsapp::updateOrCreate(
            ['user_id' => $user->id],
            ['phone' => $validated['phone'], 'code_hash' => Hash::make($code), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'last_sent_at' => now()]
        );

        return Redirect::route('profile.edit')->with('status', 'phone-verification-sent');
    }

    public function verifyPhoneChange(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        $verification = $user->phoneChangeVerification;

        if (! $verification || $verification->expires_at->isPast()) {
            optional($verification)->delete();
            return Redirect::route('profile.edit')->withErrors(['code' => 'Kode verifikasi sudah kedaluwarsa. Minta kode baru.']);
        }
        if ($verification->attempts >= 5 || ! Hash::check($request->code, $verification->code_hash)) {
            $verification->increment('attempts');
            return Redirect::route('profile.edit')->withErrors(['code' => 'Kode verifikasi tidak sesuai.']);
        }

        $user->update(['phone' => $verification->phone]);
        $this->syncApplicantContact($user);
        $verification->delete();

        return Redirect::route('profile.edit')->with('status', 'phone-updated');
    }

    public function resendPhoneChangeCode(Request $request, WhatsappCloudApiService $whatsapp): RedirectResponse
    {
        $verification = $request->user()->phoneChangeVerification;
        if (! $verification) return Redirect::route('profile.edit');
        if ($verification->last_sent_at?->gt(now()->subMinute())) {
            return Redirect::route('profile.edit')->withErrors(['code' => 'Tunggu satu menit sebelum meminta kode baru.']);
        }

        $code = (string) random_int(100000, 999999);
        try {
            $whatsapp->send($verification->phone, "Kode verifikasi perubahan nomor WhatsApp SPMB: {$code}. Kode berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.");
        } catch (Throwable $exception) {
            report($exception);
            return Redirect::route('profile.edit')->withErrors(['code' => 'Kode belum dapat dikirim ke nomor baru.']);
        }

        $verification->update(['code_hash' => Hash::make($code), 'attempts' => 0, 'expires_at' => now()->addMinutes(10), 'last_sent_at' => now()]);
        return Redirect::route('profile.edit')->with('status', 'phone-verification-sent');
    }

    private function syncApplicantContact($user): void
    {
        if ($user->pendaftar) {
            BiodataPendaftar::updateOrCreate(
                ['applicant_id' => $user->pendaftar->id],
                ['full_name' => $user->name]
            );
            KontakPendaftar::updateOrCreate(
                ['applicant_id' => $user->pendaftar->id],
                ['phone' => $user->phone]
            );
        }
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar_choice' => ['nullable', 'regex:/^character_([1-9]|10)$/'],
            'avatar_crop' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        if (! empty($validated['avatar_crop'])) {
            if (! preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $validated['avatar_crop'], $matches)) {
                return back()->withErrors(['avatar_crop' => 'Format foto profil tidak valid.']);
            }
            $image = base64_decode($matches[2], true);
            if ($image === false || strlen($image) > 3 * 1024 * 1024) {
                return back()->withErrors(['avatar_crop' => 'Ukuran foto profil maksimal 3 MB.']);
            }
            $info = @getimagesizefromstring($image);
            if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true)
                || $info['mime'] !== 'image/'.$matches[1]) {
                return back()->withErrors(['avatar_crop' => 'File foto profil harus berupa gambar PNG, JPEG, atau WebP.']);
            }
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
            $path = 'profiles/'.Str::uuid().'.'.$extension;
            try {
                // The disk creates missing directories and checks failed writes.
                if (! Storage::disk('public')->put($path, $image)) {
                    return back()->withErrors(['avatar_crop' => 'Foto belum dapat disimpan. Silakan coba lagi.']);
                }
                $user->update(['profile_photo_path' => 'storage/'.$path]);
            } catch (Throwable $exception) {
                report($exception);
                return back()->withErrors(['avatar_crop' => 'Foto belum dapat disimpan. Silakan coba lagi.']);
            }
        } elseif (! empty($validated['avatar_choice'])) {
            $user->update(['avatar_choice' => $validated['avatar_choice'], 'profile_photo_path' => null]);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
