<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BiodataPendaftar;
use App\Models\KontakPendaftar;
use App\Models\KunjunganPendaftar;
use App\Models\User;
use App\Support\FullNameNormalizer;
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
            'visit_id' => ['nullable', 'integer'],
        ], [
            'phone.regex' => 'No. WhatsApp harus diawali 08 dan berisi 10 sampai 15 digit angka.',
            'phone.unique' => 'No. WhatsApp ini sudah terdaftar. Silakan langsung masuk atau hubungi panitia.',
        ]);

        $visit = null;
        if ($request->filled('visit_id')) {
            $visit = KunjunganPendaftar::query()
                ->whereNull('applicant_id')
                ->find($request->integer('visit_id'));

            if (! $visit || ! $this->visitMatchesRegistration($visit, (string) $request->name, (string) $request->phone)) {
                throw ValidationException::withMessages([
                    'name' => 'Data kunjungan sudah berubah. Periksa kembali sebelum membuat akun.',
                ]);
            }
        }

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

        if ($visit) {
            $linked = KunjunganPendaftar::query()
                ->whereKey($visit->id)
                ->whereNull('applicant_id')
                ->update(['applicant_id' => $pendaftar->id]);

            if (! $linked) {
                throw ValidationException::withMessages([
                    'name' => 'Data kunjungan baru saja terhubung ke akun lain. Silakan hubungi panitia.',
                ]);
            }
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('peserta.dashboard', absolute: false));
    }

    /** Find a visit or existing account before a new participant account is created. */
    public function checkVisit(Request $request)
    {
        $name = ParticipantNameFormatter::titleCase((string) $request->query('name'));
        $phone = $this->normalizePhone((string) $request->query('phone'));

        if ($name === '' && ! preg_match('/^08[0-9]{8,13}$/', $phone)) {
            return response()->json(['existing_account' => null, 'visits' => []]);
        }

        $existing = User::query()->where('phone', $phone)->first();
        if ($existing) {
            return response()->json([
                'existing_account' => [
                    'name' => $existing->name,
                    'phone' => $this->maskPhone($existing->phone),
                ],
                'visits' => [],
            ]);
        }

        $normalizedName = FullNameNormalizer::normalize($name);
        $visits = KunjunganPendaftar::query()
            ->whereNull('applicant_id')
            ->where(function ($query) use ($normalizedName, $phone) {
                if ($normalizedName !== '') {
                    $query->where('normalized_full_name', $normalizedName);
                }

                if (preg_match('/^08[0-9]{8,13}$/', $phone)) {
                    $query->orWhere('visitor_phone', $phone)
                        ->orWhere('parent_phone', $phone);
                }
            })
            ->latest('visited_at')
            ->limit(3)
            ->get()
            ->map(fn (KunjunganPendaftar $visit) => [
                'id' => $visit->id,
                'name' => $visit->full_name,
                'school' => $visit->origin_school ?: 'Sekolah belum dicatat',
                'student_phone' => $this->maskPhone($visit->visitor_phone),
                'parent_phone' => $visit->parent_phone ? $this->maskPhone($visit->parent_phone) : null,
                'match_reason' => $this->visitMatchReason($visit, $normalizedName, $phone),
            ]);

        return response()->json(['existing_account' => null, 'visits' => $visits]);
    }

    private function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    private function maskPhone(?string $phone): string
    {
        $phone = (string) $phone;

        return preg_replace('/^(\d{4})\d+(\d{3})$/', '$1••••$2', $phone) ?: $phone;
    }

    private function visitMatchesRegistration(KunjunganPendaftar $visit, string $name, string $phone): bool
    {
        $normalizedName = FullNameNormalizer::normalize($name);

        return ($normalizedName !== '' && $visit->normalized_full_name === $normalizedName)
            || $visit->visitor_phone === $phone
            || $visit->parent_phone === $phone;
    }

    private function visitMatchReason(KunjunganPendaftar $visit, string $normalizedName, string $phone): string
    {
        $reasons = [];
        if ($normalizedName !== '' && $visit->normalized_full_name === $normalizedName) $reasons[] = 'nama sama';
        if ($visit->visitor_phone === $phone) $reasons[] = 'nomor WhatsApp siswa sama';
        if ($visit->parent_phone === $phone) $reasons[] = 'nomor WhatsApp orang tua sama';

        return implode(' · ', $reasons) ?: 'data kunjungan cocok';
    }
}
