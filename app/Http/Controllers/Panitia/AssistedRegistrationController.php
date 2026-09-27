<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Peserta\SekolahAsalController as PesertaSekolahAsalController;
use App\Models\{Agama, AlamatPendaftar, BiodataPendaftar, DataAyah, DataIbu, DataWali, JalurPendaftaran, Jurusan, KontakPendaftar, Pendaftar, ReferensiSekolah, SekolahAsal, User, Pekerjaan, Pendidikan, Penghasilan};
use App\Support\FormFieldCatalog;
use App\Support\ParticipantNameFormatter;
use App\Services\{WhatsappCloudApiService, ParticipantActivationService};
use App\Support\PendaftarSetup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AssistedRegistrationController extends Controller
{
    public function create()
    {
        return view('panitia.pendaftaran_bantuan.form', $this->formData() + ['pendaftar' => null]);
    }

    /** Reuses the participant's official SMP/MTs-only reference search. */
    public function searchSchool(Request $request)
    {
        return app(PesertaSekolahAsalController::class)->search($request);
    }

    public function store(Request $request, ParticipantActivationService $activation, WhatsappCloudApiService $whatsapp)
    {
        $data = $this->validateForm($request, true);

        try {
            [$pendaftar, $user] = DB::transaction(function () use ($data) {
                if (User::where('phone', $data['phone'])->exists()) {
                    return [null, null];
                }
                $role = \App\Models\Peran::firstOrCreate(['name' => 'peserta'], ['description' => 'Peserta SPMB']);
                $user = User::create([
                    'name' => $data['full_name'], 'phone' => $data['phone'], 'email' => null,
                    // Panitia never chooses or sees a student's password.
                    'password' => Hash::make(Str::random(64)), 'role_id' => $role->id,
                ]);
                $pendaftar = PendaftarSetup::getOrCreateFor($user);
                $this->saveForm($pendaftar, $data);

                return [$pendaftar, $user];
            }, 3);
        } catch (Throwable $exception) {
            Log::warning('Pendaftaran dibantu gagal dibuat.', ['operator_id' => Auth::id(), 'error' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['form' => 'Data belum dapat disimpan. Coba lagi.']);
        }

        if (! $pendaftar) {
            return back()->withInput()->withErrors(['phone' => 'Nomor WhatsApp ini sudah mempunyai akun. Gunakan data pendaftar yang sudah ada.']);
        }

        try {
            $activation->send($user, $whatsapp);
            $message = 'Pendaftaran dibantu tersimpan. Tautan membuat kata sandi sudah dikirim ke WhatsApp siswa.';
        } catch (Throwable $exception) {
            Log::warning('Tautan aktivasi siswa belum terkirim.', ['applicant_id' => $pendaftar->id, 'error' => $exception->getMessage()]);
            $message = 'Pendaftaran dibantu tersimpan, tetapi tautan aktivasi WhatsApp belum terkirim. Hubungi Admin untuk pengecekan pengiriman.';
        }

        return redirect()->route($this->routeName('pendaftar.show'), $pendaftar)->with('success', $message);
    }

    public function edit(Pendaftar $pendaftar)
    {
        $pendaftar->load(['user', 'biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal', 'kontak']);

        return view('panitia.pendaftaran_bantuan.form', $this->formData() + compact('pendaftar'));
    }

    public function update(Request $request, Pendaftar $pendaftar)
    {
        $pendaftar->load('user');
        $data = $this->validateForm($request, false, $pendaftar);
        $this->saveForm($pendaftar, $data);
        $pendaftar->user?->update(['name' => $data['full_name'], 'phone' => $data['phone']]);

        return redirect()->route($this->routeName('pendaftar.show'), $pendaftar)->with('success', 'Data formulir siswa berhasil diperbarui.');
    }

    private function validateForm(Request $request, bool $new, ?Pendaftar $pendaftar = null): array
    {
        $request->merge(['phone' => $this->normalizePhone((string) $request->input('phone'))]);
        $phoneRule = Rule::unique('pengguna', 'phone');
        if (! $new && $pendaftar?->user) $phoneRule->ignore($pendaftar->user->id);

        $rules = [
            // Account credentials must always be collected; the rest follows the Admin field configuration.
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'regex:/^08[0-9]{8,13}$/', $phoneRule],
            'admission_path_id' => ['nullable', Rule::exists('jalur_pendaftaran', 'id')->where('status', 'aktif')],
        ];
        $fieldRule = fn (string $key, array $rule) => FormFieldCatalog::isRequired($key)
            ? array_merge(['required'], $rule)
            : array_merge(['nullable'], $rule);
        $add = function (string $catalogKey, string $input, array $rule) use (&$rules, $fieldRule): void {
            if (FormFieldCatalog::isEnabled($catalogKey)) $rules[$input] = $fieldRule($catalogKey, $rule);
        };

        $add('nisn', 'nisn', ['digits:10']);
        $add('nik', 'nik', ['digits:16']);
        $add('no_kartu_keluarga', 'no_kk', ['digits:16']);
        $add('jenis_kelamin', 'gender', [Rule::in(['L', 'P'])]);
        $add('tempat_lahir', 'birth_place', ['string', 'max:100']);
        $add('tanggal_lahir', 'birth_date', ['date']);
        $add('agama', 'religion', [Rule::in(Agama::pluck('nama')->all())]);
        $add('alamat', 'address', ['string', 'max:1000']);
        foreach (['rt' => ['string', 'max:5'], 'rw' => ['string', 'max:5'], 'kelurahan' => ['string', 'max:255'], 'kecamatan' => ['string', 'max:255'], 'kabupaten_kota' => ['string', 'max:255'], 'provinsi' => ['string', 'max:255'], 'kode_pos' => ['string', 'max:10']] as $key => $rule) $add($key, match($key) { 'kelurahan' => 'village', 'kecamatan' => 'district', 'kabupaten_kota' => 'city', 'kode_pos' => 'postal_code', default => $key }, $rule);
        $add('jarak_ke_sekolah', 'distance_range', ['string', 'max:50']);
        $add('asal_sekolah', 'school_name', ['string', 'max:150']);
        $add('npsn', 'npsn', ['digits:8']);
        $add('alamat_sekolah', 'school_address', ['string', 'max:1000']);
        $add('tahun_lulus', 'graduation_year', ['integer', 'min:1990', 'max:'.(now()->year + 1)]);
        if (collect(['asal_sekolah', 'npsn', 'alamat_sekolah', 'tahun_lulus'])->contains(fn (string $key) => FormFieldCatalog::isEnabled($key))) {
            $rules['referensi_sekolah_id'] = ['required', Rule::exists('referensi_sekolah', 'id')];
        }
        if (FormFieldCatalog::isEnabled('jurusan')) {
            $rules['major_choice_1'] = $fieldRule('jurusan', [Rule::exists('jurusan', 'id')->where('status', 'aktif')]);
            $rules['major_choice_2'] = ['nullable', 'different:major_choice_1', Rule::exists('jurusan', 'id')->where('status', 'aktif')];
        }
        $add('email', 'email', ['email', 'max:150']);

        foreach (['ayah' => 'father', 'ibu' => 'mother', 'wali' => 'guardian'] as $catalogPrefix => $inputPrefix) {
            $add("nama_{$catalogPrefix}", "{$inputPrefix}_name", ['string', 'max:150']);
            $add("nik_{$catalogPrefix}", "{$inputPrefix}_nik", ['string', 'max:20']);
            $add("no_hp_{$catalogPrefix}", "{$inputPrefix}_phone", ['string', 'max:20']);
            $add("pendidikan_{$catalogPrefix}", "{$inputPrefix}_education", [Rule::in(Pendidikan::pluck('nama')->all())]);
            $add("pekerjaan_{$catalogPrefix}", "{$inputPrefix}_occupation", [Rule::in(Pekerjaan::pluck('nama')->all())]);
            $add("penghasilan_{$catalogPrefix}", "{$inputPrefix}_income", [Rule::in(Penghasilan::pluck('nama')->all())]);
        }

        $data = $request->validate($rules, [
            'referensi_sekolah_id.required' => 'Pilih sekolah SMP atau MTs dari hasil pencarian.',
        ]);
        $data['full_name'] = ParticipantNameFormatter::titleCase($data['full_name']);

        if (isset($data['referensi_sekolah_id'])) {
            $reference = ReferensiSekolah::findOrFail($data['referensi_sekolah_id']);
            if (! in_array(strtoupper(trim((string) $reference->bentuk_pendidikan)), ['SMP', 'MTS'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'referensi_sekolah_id' => 'Sekolah asal harus berjenjang SMP atau MTs.',
                ]);
            }

            $data = array_merge($data, [
                'school_name' => $reference->nama,
                'npsn' => $reference->npsn,
                'school_address' => $reference->alamat,
                'school_bentuk_pendidikan' => $reference->bentuk_pendidikan,
                'school_status' => $reference->status,
                'school_village' => $reference->desa_kelurahan,
                'school_district' => $reference->kecamatan,
                'school_city' => $reference->kabupaten_kota,
                'school_province' => $reference->provinsi,
            ]);
        }

        return $data;
    }

    private function saveForm(Pendaftar $pendaftar, array $data): void
    {
        $this->updateConfigured(BiodataPendaftar::class, $pendaftar, $data, [
            'full_name' => 'full_name', 'nisn' => 'nisn', 'nik' => 'nik', 'no_kk' => 'no_kk', 'gender' => 'gender', 'birth_place' => 'birth_place', 'birth_date' => 'birth_date', 'religion' => 'religion',
        ]);
        $contact = ['phone' => $data['phone']];
        if (array_key_exists('email', $data)) $contact['email'] = $data['email'];
        KontakPendaftar::updateOrCreate(['applicant_id' => $pendaftar->id], $contact);

        $this->updateConfigured(AlamatPendaftar::class, $pendaftar, $data, [
            'address' => 'address', 'rt' => 'rt', 'rw' => 'rw', 'village' => 'village', 'district' => 'district', 'city' => 'city', 'province' => 'province', 'postal_code' => 'postal_code', 'distance_range' => 'distance_range',
        ]);
        if (array_key_exists('distance_range', $data)) {
            AlamatPendaftar::where('applicant_id', $pendaftar->id)->update(['distance_point' => $this->distancePoint($data['distance_range']), 'distance_to_school' => $this->distanceMeter($data['distance_range'])]);
        }
        if (isset($data['referensi_sekolah_id'])) {
            SekolahAsal::updateOrCreate(['applicant_id' => $pendaftar->id], [
                'referensi_sekolah_id' => $data['referensi_sekolah_id'],
                'school_name' => $data['school_name'],
                'npsn' => $data['npsn'],
                'school_address' => $data['school_address'],
                'bentuk_pendidikan' => $data['school_bentuk_pendidikan'],
                'status_sekolah' => $data['school_status'],
                'desa_kelurahan' => $data['school_village'],
                'kecamatan' => $data['school_district'],
                'kabupaten_kota' => $data['school_city'],
                'provinsi' => $data['school_province'],
                'graduation_year' => $data['graduation_year'] ?? null,
            ]);
        } else {
            $this->updateConfigured(SekolahAsal::class, $pendaftar, $data, [
                'school_name' => 'school_name', 'npsn' => 'npsn', 'school_address' => 'school_address', 'graduation_year' => 'graduation_year',
            ]);
        }
        $this->saveParent(DataAyah::class, $pendaftar, $data, 'father');
        $this->saveParent(DataIbu::class, $pendaftar, $data, 'mother');
        $this->saveParent(DataWali::class, $pendaftar, $data, 'guardian');

        $choices = [];
        foreach (['major_choice_1', 'major_choice_2', 'admission_path_id'] as $key) if (array_key_exists($key, $data)) $choices[$key] = $data[$key];
        if ($choices) $pendaftar->update($choices);
    }

    private function updateConfigured(string $model, Pendaftar $pendaftar, array $data, array $map): void
    {
        $attributes = [];
        foreach ($map as $column => $input) if (array_key_exists($input, $data)) $attributes[$column] = $data[$input];
        if ($attributes) $model::updateOrCreate(['applicant_id' => $pendaftar->id], $attributes);
    }

    private function saveParent(string $model, Pendaftar $pendaftar, array $data, string $prefix): void
    {
        $map = ['name' => "{$prefix}_name", 'nik' => "{$prefix}_nik", 'phone' => "{$prefix}_phone", 'education' => "{$prefix}_education", 'occupation' => "{$prefix}_occupation", 'income' => "{$prefix}_income"];
        $attributes = [];
        foreach ($map as $column => $input) if (array_key_exists($input, $data)) $attributes[$column] = $data[$input];
        if ($attributes) $model::updateOrCreate(['applicant_id' => $pendaftar->id], $attributes);
    }

    private function formData(): array
    {
        return [
            'agamas' => Agama::orderBy('nama')->pluck('nama'),
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('name')->get(),
            'jalurs' => JalurPendaftaran::where('status', 'aktif')->orderBy('name')->get(),
            'fieldGroups' => FormFieldCatalog::groups(),
            'enabledFields' => FormFieldCatalog::enabled(),
            'pendidikans' => Pendidikan::orderBy('id')->pluck('nama'),
            'pekerjaans' => Pekerjaan::orderBy('nama')->pluck('nama'),
            'penghasilans' => Penghasilan::orderBy('id')->pluck('nama'),
        ];
    }

    private function distancePoint(?string $distance): ?int
    {
        return match ($distance) {
            '0 - 1000 meter' => 500, '1001 - 3000 meter' => 400, '3001 - 5000 meter' => 300,
            '5001 - 10000 meter' => 200, 'Lebih dari 10000 meter' => 100, default => null,
        };
    }


    private function distanceMeter(?string $distance): ?int
    {
        return match ($distance) {
            '0 - 1000 meter' => 1000, '1001 - 3000 meter' => 3000, '3001 - 5000 meter' => 5000,
            '5001 - 10000 meter' => 10000, 'Lebih dari 10000 meter' => 10001, default => null,
        };
    }

    private function routeName(string $name): string
    {
        if (request()->routeIs('admin.*')) return 'admin.' . $name;
        if (request()->routeIs('bendahara.*')) return 'bendahara.' . $name;
        if (request()->routeIs('kepala-sekolah.*')) return 'kepala-sekolah.' . $name;

        return 'panitia.' . $name;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
