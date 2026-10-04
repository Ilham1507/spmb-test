<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $request->validate([
            'token' => ['required'],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $attemptKey = 'spmb-password-code:'.hash('sha256', $request->phone);
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return back()->withErrors(['phone' => 'Terlalu banyak percobaan. Silakan coba lagi setelah 15 menit.']);
        }

        $user = DB::transaction(function () use ($request) {
            $reset = DB::table('password_reset_tokens')->where('email', $request->phone)->lockForUpdate()->first();
            $expired = ! $reset?->created_at || Carbon::parse($reset->created_at)->addMinutes(5)->isPast();
            if (! $reset || $expired || ! Hash::check($request->token, $reset->token)) return null;
            $user = User::where('phone', $request->phone)->lockForUpdate()->first();
            if (! $user) return null;
            $user->forceFill(['password' => Hash::make($request->password), 'remember_token' => Str::random(60)])->save();
            DB::table('password_reset_tokens')->where('email', $request->phone)->delete();
            return $user;
        });
        if (! $user) {
            RateLimiter::hit($attemptKey, 900);
            return back()->withInput($request->only('phone'))->withErrors(['phone' => 'Kode atau tautan tidak valid atau sudah kedaluwarsa. Silakan minta yang baru.']);
        }

        RateLimiter::clear($attemptKey);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Kata sandi berhasil diperbarui. Silakan masuk.');
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
