<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\BiodataPendaftar;
use App\Models\Pendaftar;
use App\Models\Agama;
use App\Support\PendaftarSetup;
use App\Support\FormFieldCatalog;
use App\Support\ParticipantNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class BiodataController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getOrCreatePendaftar();
        $biodata = $pendaftar->biodata;
        $agamas = Agama::orderBy('nama')->pluck('nama');

        return view('peserta.biodata.index', compact('biodata', 'pendaftar', 'agamas'));
    }

    public function store(Request $request)
    {
        $required = fn (string $key, string $rules) => FormFieldCatalog::isRequired($key) ? "required|{$rules}" : "nullable|{$rules}";
        $rules = [
            'nisn'          => $required('nisn', 'digits:10'),
            'nik'           => $required('nik', 'digits:16'),
            'no_kk'         => $required('no_kartu_keluarga', 'digits:16'),
            'jenis_kelamin' => $required('jenis_kelamin', 'in:L,P'),
            'tempat_lahir'  => $required('tempat_lahir', 'string|max:100'),
            'tanggal_lahir' => $required('tanggal_lahir', 'date'),
            'agama'         => array_merge(
                explode('|', $required('agama', 'string|max:50')),
                [Rule::in(Agama::pluck('nama')->all())]
            ),
        ];

        $validated = $request->validate($rules, [
            'nisn.digits' => 'NISN harus terdiri dari tepat 10 digit angka.',
            'nik.digits' => 'NIK harus terdiri dari tepat 16 digit angka sesuai Kartu Keluarga/KTP.',
            'no_kk.digits' => 'Nomor KK harus terdiri dari tepat 16 digit angka.',
            'required' => ':attribute wajib diisi sebelum melanjutkan.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $user = Auth::user();
                if (! $user) {
                    throw new \RuntimeException('Sesi siswa tidak ditemukan.');
                }

                $pendaftar = $this->getOrCreatePendaftar();
                $fullName = ParticipantNameFormatter::titleCase((string) $user->name);
                if ($user->name !== $fullName) {
                    $user->update(['name' => $fullName]);
                }

                BiodataPendaftar::updateOrCreate(
                    ['applicant_id' => $pendaftar->id],
                    [
                        'full_name' => $fullName,
                        'nisn' => $validated['nisn'] ?? null,
                        'nik' => $validated['nik'] ?? null,
                        'no_kk' => $validated['no_kk'] ?? null,
                        'gender' => $validated['jenis_kelamin'] ?? null,
                        'birth_place' => $validated['tempat_lahir'] ?? null,
                        'birth_date' => $validated['tanggal_lahir'] ?? null,
                        'religion' => $validated['agama'] ?? null,
                    ]
                );
            }, 3);
        } catch (Throwable $exception) {
            Log::error('Penyimpanan biodata peserta gagal.', [
                'user_id' => Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Data diri belum dapat disimpan. Silakan coba lagi; data yang diisi tetap dipertahankan.');
        }

        return $this->redirectAfterParticipantSave($request, 'peserta.alamat', 'Biodata berhasil disimpan.', 'peserta.alamat');
    }

    private function getOrCreatePendaftar(): Pendaftar
    {
        return PendaftarSetup::getOrCreateFor(Auth::user());
    }

}
