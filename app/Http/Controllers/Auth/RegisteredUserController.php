<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BiodataPendaftar;
use App\Models\KontakPendaftar;
use App\Models\User;
use App\Support\PendaftarSetup;
use App\Support\ParticipantNameFormatter;
use App\Support\SpmbConfiguration;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        abort_unless(SpmbConfiguration::registrationIsOpen(), 403, SpmbConfiguration::registrationUnavailableMessage());
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (! SpmbConfiguration::registrationIsOpen()) {
            throw ValidationException::withMessages(['phone' => SpmbConfiguration::registrationUnavailableMessage()]);
        }
        $request->merge([
            'phone' => $this->normalizePhone((string) $request->input('phone')),
            'name' => ParticipantNameFormatter::titleCase((string) $request->input('name')),
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/', 'unique:pengguna,phone'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'No. WhatsApp harus diawali 08 dan berisi 10 sampai 15 digit angka.',
            'phone.unique' => 'No. WhatsApp ini sudah terdaftar. Silakan langsung masuk atau hubungi panitia.',
        ]);

        $pesertaRole = \App\Models\Peran::where('name', 'peserta')->first();

        $user = User::create([
            'name' => $request->name,
            'email' => null,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $pesertaRole?->id,
        ]);

        $pendaftar = PendaftarSetup::getOrCreateFor($user);

        BiodataPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            ['full_name' => $request->name]
        );

        KontakPendaftar::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            ['phone' => $request->phone]
        );

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('peserta.dashboard', absolute: false));
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }
}
